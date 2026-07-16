# Tutorial de Instalação e Configuração (Partes 1 e 2)

Siga os passos abaixo para colocar o **smbcontrol** no ar.

## Parte 1: Importação do Banco de Dados

1. Acesse o servidor e vá para o diretório raiz do projeto:
   ```bash
   cd /var/www/smbcontrol
   ```

2. Acesse o MySQL/MariaDB como root. (Se você usa `sudo` ou se o root não tem senha, rode sem o `-p`):
   ```bash
   sudo mysql -u root
   ```

3. Dentro do MySQL, importe o arquivo SQL do projeto:
   ```sql
   source /var/www/smbcontrol/database/database.sql;
   exit;
   ```

   *(Alternativamente, você pode importar direto da linha de comando com `sudo mysql -u root < /var/www/smbcontrol/database/database.sql`)*.

4. Verifique a configuração de acesso:
   Abra o arquivo `config/database.php` e garanta que o usuário (`root`) e a senha estejam corretos.

## Parte 2: Configuração do Servidor Web (URL Rewrite)

O **smbcontrol** usa uma arquitetura de "Front Controller", o que significa que todas as requisições devem passar pelo arquivo `public/index.php`.

### Se você usa Apache2

1. Crie (ou confira se já criamos) o arquivo `.htaccess` dentro da pasta `public/`:
   ```bash
   nano /var/www/smbcontrol/public/.htaccess
   ```

   O conteúdo deve ser:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

2. Certifique-se de que o Apache permite o uso de `.htaccess`. No arquivo `/etc/apache2/apache2.conf` ou no seu VirtualHost, o bloco do diretório deve ter `AllowOverride All`:
   ```apache
   <Directory /var/www/smbcontrol/public>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. Habilite o módulo `rewrite` e reinicie o Apache:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

4. Habilite e inicie o serviço de logs de sistema (necessário para a aba Audit funcionar):
   ```bash
   sudo systemctl enable rsyslog
   sudo systemctl start rsyslog
   ```

### Se você usa Nginx

Edite o seu bloco de configuração (`/etc/nginx/sites-available/default` ou similar):

```nginx
server {
    listen 80;
    server_name seu-dominio-ou-ip.com;
    root /var/www/smbcontrol/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Altere para a versão do seu PHP
    }
}
```

Reinicie o Nginx:
```bash
sudo systemctl restart nginx
```

Pronto! Agora você já pode acessar o painel pelo navegador e fazer login usando o usuário e senha **admin**.
