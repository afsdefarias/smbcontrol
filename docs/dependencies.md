# Dependências para Instalação

Para que o **smbcontrol** (versão 1.0) funcione corretamente no Debian ou derivados (como Ubuntu), o servidor precisa ter as seguintes dependências instaladas.

## Requisitos de Sistema

- **Sistema Operacional**: Linux (Debian 11/12 ou Ubuntu 22.04+)
- **Samba**: Servidor de arquivos.
- **Servidor Web**: Apache2 ou Nginx.
- **PHP**: PHP 8.0 ou superior.
- **Banco de Dados**: MariaDB ou MySQL.

## Instalação via APT

Execute o comando abaixo como `root` ou usando `sudo` para instalar tudo o que é necessário:

```bash
sudo apt update
sudo apt install -y apache2 mariadb-server php php-cli php-mysql php-pdo samba rsyslog acl smbclient
```

*Nota: Se você for utilizar Nginx, instale `nginx` e `php-fpm` no lugar do `apache2` e `php`.*

## Módulos do PHP

Certifique-se de que a extensão PDO para MySQL está habilitada. No Debian/Ubuntu, ao instalar o pacote `php-mysql`, ele já costuma habilitar o PDO automaticamente.

## Permissões de Sudo (Muito Importante)

O painel executa comandos no nível do sistema utilizando o usuário do servidor web (`www-data`). Para que as funções do painel funcionem corretamente (como gerenciar o serviço, arquivos e usuários), adicione as regras no arquivo `sudoers`.

Execute `sudo visudo` no terminal.

Role a tela até o final do arquivo (usando as setas do teclado) e adicione exatamente a linha abaixo. Ela diz ao Linux que o usuário www-data tem permissão exclusiva para iniciar, parar, reiniciar e checar o status do smbd como root, sem bloqueios de senha (além dos demais comandos necessários para editar arquivos e pastas):

```sudoers
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl *, /usr/bin/cat *, /usr/bin/testparm *, /usr/bin/cp *, /usr/bin/mkdir *, /usr/bin/chown *, /usr/bin/chmod *, /usr/bin/setfacl *, /usr/bin/getfacl *, /usr/bin/find *, /usr/sbin/useradd *, /usr/bin/smbpasswd *, /usr/sbin/groupadd *, /usr/sbin/usermod *, /usr/bin/gpasswd *, /usr/sbin/userdel *, /usr/bin/pdbedit *, /usr/sbin/mkfs.ext4 *, /usr/sbin/mkfs.xfs *, /usr/sbin/blkid *, /usr/bin/lsblk *, /usr/bin/mount *, /usr/bin/umount *, /usr/bin/sed *, /usr/bin/rmdir *, /usr/bin/touch *, /usr/bin/rm *, /usr/bin/sh *, /bin/sh *
```

*(Nota: O painel utiliza `sh -c` para concatenar arquivos, portanto a permissão do `sh` é necessária. Caso o caminho do seu sistema mude, ajuste `/usr/bin/` para `/bin/` ou `/sbin/` conforme necessário).*

3. **Salve e feche o arquivo.**
A forma de salvar depende de qual editor de texto o visudo abriu no seu terminal:

- **Se abriu no editor nano (mais comum)**: Aperte `Ctrl + O` (para salvar), `Enter` (para confirmar o nome do arquivo) e `Ctrl + X` (para sair).
- **Se abriu no vi/vim**: Aperte a tecla `Esc`, digite `:wq` e aperte `Enter`.

Assim que você salvar e sair do visudo, volte para a interface web do smbcontrol e teste os recursos novamente. Os comandos agora passarão lisos pelo sistema operacional!
