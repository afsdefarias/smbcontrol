#!/usr/bin/env bash
set -Eeuo pipefail

REPO_URL="${SMBCONTROL_REPO_URL:-https://github.com/afsdefarias/smbcontrol.git}"
BRANCH="${SMBCONTROL_BRANCH:-main}"
APP_DIR="${SMBCONTROL_DIR:-/var/www/smbcontrol}"
STATE_DIR=/etc/smbcontrol
STATE_FILE="$STATE_DIR/install.conf"

log() { printf '[smbcontrol] %s\n' "$*"; }
die() { printf '[smbcontrol] ERROR: %s\n' "$*" >&2; exit 1; }

if [ "$(id -u)" -ne 0 ]; then
    die 'Run this installer as root: curl ... | sudo bash'
fi

command -v git >/dev/null 2>&1 || true

source_os() {
    [ -r /etc/os-release ] || die '/etc/os-release was not found.'
    # shellcheck disable=SC1091
    . /etc/os-release
    case "${ID:-}" in
        debian|ubuntu)
            OS_FAMILY=debian
            WEB_USER=www-data
            WEB_GROUP=www-data
            APACHE_SERVICE=apache2
            DB_SERVICE=mariadb
            SAMBA_SERVICE=smbd
            ;;
        arch)
            OS_FAMILY=arch
            WEB_USER=http
            WEB_GROUP=http
            APACHE_SERVICE=httpd
            DB_SERVICE=mariadb
            SAMBA_SERVICE=smb
            ;;
        *) die "Unsupported distribution: ${ID:-unknown}. Supported: Debian, Ubuntu, and Arch Linux." ;;
    esac
}

install_packages() {
    log 'Installing system dependencies.'
    if [ "$OS_FAMILY" = debian ]; then
        export DEBIAN_FRONTEND=noninteractive
        apt-get update
        apt-get install -y apache2 libapache2-mod-php mariadb-server php php-cli php-mysql php-curl php-mbstring php-xml php-zip php-gd php-intl samba rsyslog acl smbclient sudo git curl
        a2enmod rewrite >/dev/null
    else
        pacman -Sy --needed --noconfirm apache mariadb php php-apache samba rsyslog acl smbclient sudo git curl
        if [ ! -d /var/lib/mysql/mysql ]; then
            mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
        fi
    fi

    systemctl enable --now "$DB_SERVICE"
    systemctl enable --now "$APACHE_SERVICE"
    systemctl enable --now "$SAMBA_SERVICE"
    systemctl enable --now rsyslog || true
}

install_source() {
    mkdir -p "$(dirname "$APP_DIR")"
    if [ -e "$APP_DIR" ] && [ ! -d "$APP_DIR/.git" ]; then
        backup="${APP_DIR}.backup.$(date +%Y%m%d%H%M%S)"
        log "Existing non-Git directory found; preserving it at $backup."
        mv "$APP_DIR" "$backup"
    fi

    if [ -d "$APP_DIR/.git" ]; then
        log 'Updating the existing source tree without touching system Samba data or the database.'
        git -C "$APP_DIR" fetch --depth 1 origin "$BRANCH"
        git -C "$APP_DIR" checkout -q "$BRANCH"
        git -C "$APP_DIR" reset --hard "origin/$BRANCH"
    else
        log "Cloning $REPO_URL."
        git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
    fi
}

random_secret() {
    od -An -N24 -tx1 /dev/urandom | tr -d ' \n'
}

configure_database() {
    mkdir -p "$APP_DIR/config"
    if [ -f "$APP_DIR/config/database.php" ]; then
        log 'Existing database.php preserved.'
        return
    fi

    db_name=smbcontrol
    db_user=smbcontrol_user
    db_password="$(random_secret)"
    log 'Creating the MariaDB database and dedicated database user.'
    mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$db_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$db_user'@'127.0.0.1' IDENTIFIED BY '$db_password';
ALTER USER '$db_user'@'127.0.0.1' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON \`$db_name\`.* TO '$db_user'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

    mysql --protocol=socket -uroot "$db_name" < "$APP_DIR/database/database.sql"
    umask 077
    printf "<?php\nreturn [\n    'host' => '127.0.0.1',\n    'dbname' => '%s',\n    'user' => '%s',\n    'password' => '%s'\n];\n" "$db_name" "$db_user" "$db_password" > "$APP_DIR/config/database.php"

    admin_password=''
    if [ -r /dev/tty ]; then
        while [ "${#admin_password}" -lt 8 ]; do
            printf 'Choose the initial smbcontrol admin password (minimum 8 characters): ' > /dev/tty
            IFS= read -r -s admin_password < /dev/tty || true
            printf '\n' > /dev/tty
            [ "${#admin_password}" -ge 8 ] || printf 'Password is too short.\n' > /dev/tty
        done
    else
        admin_password="$(random_secret)"
        log "No interactive terminal found; a random admin password was generated."
    fi

    admin_hash="$(ADMIN_PASSWORD="$admin_password" php -r 'echo password_hash(getenv("ADMIN_PASSWORD"), PASSWORD_BCRYPT, ["cost" => 12]);')"
    mysql --protocol=socket -uroot "$db_name" -e "UPDATE users SET password='$admin_hash' WHERE username='admin';"
    INITIAL_ADMIN_PASSWORD="$admin_password"
}

configure_sudoers() {
    log "Configuring privileged commands for the web user $WEB_USER."
    install -d -m 0755 /etc/sudoers.d
    cat > /etc/sudoers.d/smbcontrol <<EOF
$WEB_USER ALL=(root) NOPASSWD: /usr/bin/systemctl *, /bin/systemctl *, /usr/bin/cat *, /usr/bin/testparm *, /usr/bin/cp *, /usr/bin/mkdir *, /usr/bin/chown *, /usr/bin/chmod *, /usr/bin/setfacl *, /usr/bin/getfacl *, /usr/bin/find *, /usr/bin/useradd *, /usr/sbin/useradd *, /usr/bin/usermod *, /usr/sbin/usermod *, /usr/bin/smbpasswd *, /usr/sbin/smbpasswd *, /usr/bin/groupadd *, /usr/sbin/groupadd *, /usr/bin/groupdel *, /usr/sbin/groupdel *, /usr/bin/gpasswd *, /usr/sbin/gpasswd *, /usr/bin/userdel *, /usr/sbin/userdel *, /usr/bin/pdbedit *, /usr/bin/mount *, /usr/bin/umount *, /usr/bin/blkid *, /usr/sbin/blkid *, /usr/bin/lsblk *, /usr/bin/rmdir *, /usr/bin/touch *, /usr/bin/rm *, /usr/bin/sh *, /bin/sh *
EOF
    chmod 0440 /etc/sudoers.d/smbcontrol
    visudo -cf /etc/sudoers.d/smbcontrol >/dev/null
}

configure_database_permissions() {
    chown -R "$WEB_USER:$WEB_GROUP" "$APP_DIR"
    find "$APP_DIR" -type d -exec chmod 0755 {} +
    find "$APP_DIR" -type f -exec chmod 0644 {} +
    chmod 0640 "$APP_DIR/config/database.php"
    chmod 0755 "$APP_DIR/scripts/install.sh" "$APP_DIR/scripts/update.sh"
}

configure_apache() {
    log "Configuring Apache for $APP_DIR/public."
    if [ "$OS_FAMILY" = debian ]; then
        cat > /etc/apache2/sites-available/smbcontrol.conf <<EOF
<VirtualHost *:80>
    ServerName _
    DocumentRoot $APP_DIR/public
    <Directory $APP_DIR/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/smbcontrol-error.log
    CustomLog \${APACHE_LOG_DIR}/smbcontrol-access.log combined
</VirtualHost>
EOF
        a2ensite smbcontrol.conf >/dev/null
    else
        mkdir -p /etc/httpd/conf/extra
        if grep -q '^LoadModule mpm_event_module' /etc/httpd/conf/httpd.conf; then
            sed -i 's/^LoadModule mpm_event_module/#LoadModule mpm_event_module/' /etc/httpd/conf/httpd.conf
        fi
        grep -q '^LoadModule mpm_prefork_module' /etc/httpd/conf/httpd.conf || printf '\nLoadModule mpm_prefork_module modules/mod_mpm_prefork.so\n' >> /etc/httpd/conf/httpd.conf
        cat > /etc/httpd/conf/extra/smbcontrol.conf <<EOF
DocumentRoot "$APP_DIR/public"
<Directory "$APP_DIR/public">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
DirectoryIndex index.php
EOF
        grep -qF 'Include conf/extra/smbcontrol.conf' /etc/httpd/conf/httpd.conf || printf '\nInclude conf/extra/smbcontrol.conf\n' >> /etc/httpd/conf/httpd.conf
        if [ -f /etc/httpd/conf/extra/php_module.conf ]; then
            grep -qF 'Include conf/extra/php_module.conf' /etc/httpd/conf/httpd.conf || printf 'Include conf/extra/php_module.conf\n' >> /etc/httpd/conf/httpd.conf
        elif [ -f /usr/lib/httpd/modules/libphp.so ]; then
            grep -q '^LoadModule php_module' /etc/httpd/conf/httpd.conf || printf '\nLoadModule php_module modules/libphp.so\nAddHandler php-script .php\n' >> /etc/httpd/conf/httpd.conf
        fi
    fi
    apachectl -t
    systemctl restart "$APACHE_SERVICE"
}

configure_samba() {
    log 'Preserving the existing Samba configuration and enabling shares.conf inclusion.'
    touch /etc/samba/shares.conf
    if ! grep -Eiq '^[[:space:]]*include[[:space:]]*=[[:space:]]*/etc/samba/shares\.conf[[:space:]]*$' /etc/samba/smb.conf; then
        printf '\n   include = /etc/samba/shares.conf\n' >> /etc/samba/smb.conf
    fi
    testparm -s >/dev/null
    systemctl enable --now "$SAMBA_SERVICE"
}

install_update_command() {
    install -d -m 0755 "$STATE_DIR"
    cat > "$STATE_FILE" <<EOF
APP_DIR=$(printf '%q' "$APP_DIR")
REPO_URL=$(printf '%q' "$REPO_URL")
BRANCH=$(printf '%q' "$BRANCH")
WEB_USER=$(printf '%q' "$WEB_USER")
WEB_GROUP=$(printf '%q' "$WEB_GROUP")
APACHE_SERVICE=$(printf '%q' "$APACHE_SERVICE")
SAMBA_SERVICE=$(printf '%q' "$SAMBA_SERVICE")
EOF
    install -m 0755 "$APP_DIR/scripts/update.sh" /usr/local/bin/update-smbcontrol
}

source_os
install_packages
install_source
configure_database
configure_sudoers
configure_database_permissions
configure_apache
configure_samba
install_update_command

log 'Installation complete.'
printf '\nOpen: http://<server-ip>/\n'
if [ -n "${INITIAL_ADMIN_PASSWORD:-}" ]; then
    printf 'Username: admin\nPassword: %s\n' "$INITIAL_ADMIN_PASSWORD"
else
    printf 'The existing admin/database configuration was preserved.\n'
fi
printf 'Update command: update-smbcontrol\n'
