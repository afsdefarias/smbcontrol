#!/usr/bin/env bash
set -Eeuo pipefail

if [ "$(id -u)" -ne 0 ]; then
    exec sudo "$0" "$@"
fi

STATE_FILE=/etc/smbcontrol/install.conf
[ -r "$STATE_FILE" ] || { printf 'smbcontrol is not installed by the official installer.\n' >&2; exit 1; }
# shellcheck disable=SC1091
. "$STATE_FILE"

log() { printf '[smbcontrol update] %s\n' "$*"; }
die() { printf '[smbcontrol update] ERROR: %s\n' "$*" >&2; exit 1; }

command -v git >/dev/null 2>&1 || die 'git is not installed.'
[ -d "$APP_DIR/.git" ] || die "Git repository not found at $APP_DIR."

backup_dir="$(mktemp -d)"
cleanup() { rm -rf "$backup_dir"; }
trap cleanup EXIT

for protected in config/database.php .env; do
    if [ -e "$APP_DIR/$protected" ]; then
        mkdir -p "$backup_dir/$(dirname "$protected")"
        cp -a "$APP_DIR/$protected" "$backup_dir/$protected"
    fi
done

log 'Fetching the latest main branch.'
git -C "$APP_DIR" fetch --depth 1 origin "$BRANCH"
git -C "$APP_DIR" checkout -q "$BRANCH"
git -C "$APP_DIR" reset --hard "origin/$BRANCH"

for protected in config/database.php .env; do
    if [ -e "$backup_dir/$protected" ]; then
        mkdir -p "$APP_DIR/$(dirname "$protected")"
        cp -a "$backup_dir/$protected" "$APP_DIR/$protected"
    fi
done

find "$APP_DIR" -type d -exec chmod 0755 {} +
find "$APP_DIR" -type f -exec chmod 0644 {} +
[ ! -e "$APP_DIR/config/database.php" ] || chmod 0640 "$APP_DIR/config/database.php"
chown -R "$WEB_USER:$WEB_GROUP" "$APP_DIR"
find "$APP_DIR/app" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
install -m 0755 "$APP_DIR/scripts/update.sh" /usr/local/bin/update-smbcontrol

systemctl restart "$APACHE_SERVICE"
systemctl restart "$SAMBA_SERVICE"
log "Updated to $(git -C "$APP_DIR" rev-parse --short HEAD). Database, Samba data, and /etc configuration were preserved."
