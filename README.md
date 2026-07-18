# 🛡️ smbcontrol - Samba Audit & Manager

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP 8.x](https://img.shields.io/badge/PHP-8.x-777bb4.svg)](https://php.net/)
[![Platform: Linux & FreeBSD](https://img.shields.io/badge/Platform-Debian%20%7C%20Ubuntu%20%7C%20FreeBSD-lightgrey.svg)]()

> A lightweight web panel to manage Samba shares, parse `vfs_full_audit` logs, and generate detailed access reports.

🌍 **Languages:** [English](#english) | [Português (Brasil)](#português-brasil)

---

## 🇺🇸 English

### 📖 About
Managing Samba shares and extracting readable audit logs can be complex and time-consuming. This project provides a straightforward, open-source web interface designed to simplify Samba (SMB) management while offering robust file access auditing. **smbcontrol** parses Samba's `vfs_full_audit` module logs to generate clear, actionable reports on who accessed, modified, or deleted files on the network.

### ✨ Features
* **Audit Reporting:** Generate detailed reports (PDF/CSV) from `vfs_full_audit` logs.
* **Access Tracking:** Monitor who created, modified, read, or deleted specific files.
* **Share Management:** Easy-to-use interface to manage Samba shares and permissions.
* **Real-time Monitoring:** View active SMB connections.
* **Lightweight:** Designed to run smoothly on standard infrastructure without heavy dependencies.

### ⚙️ Prerequisites
* Linux (Debian/Ubuntu) or FreeBSD environment.
* Samba Server installed and configured.
* PHP 8.x
* Web Server (Apache/Nginx)
* Relational Database (MySQL/MariaDB)

### 🚀 Installation (Coming Soon)
*Instructions on how to deploy the panel will be added here.*

---

## 🇧🇷 Português (Brasil)

### 📖 Sobre
Gerenciar compartilhamentos Samba e extrair logs de auditoria legíveis pode ser complexo. Este projeto fornece uma interface web direta e de código aberto projetada para simplificar o gerenciamento do Samba (SMB), oferecendo uma auditoria robusta de acesso a arquivos. 

**🎯 Foco em Compliance e Cartórios:**
O **smbcontrol** foi idealizado para atender às rígidas exigências de compliance e normativas legais do Brasil, em especial o **Provimento 213 do CNJ** para cartórios. Ele processa os logs do módulo `vfs_full_audit` do Samba para gerar relatórios detalhados sobre quem acessou, modificou ou excluiu arquivos na rede, permitindo suporte comercial e auditoria transparente.

### ✨ Funcionalidades
* **Relatórios de Auditoria:** Geração de relatórios detalhados a partir dos logs do `vfs_full_audit`.
* **Rastreabilidade de Acesso:** Monitore quem criou, modificou, leu ou deletou arquivos específicos usando relatórios diretos do syslog.
* **Gerenciamento de Compartilhamentos:** Interface amigável para criar pastas, configurar donos (chown) e matrizes de permissão.
* **Gerenciamento de Discos:** Liste, formate e monte discos rígidos adicionais na interface web de forma simplificada.
* **Monitoramento em Tempo Real:** Visualize conexões SMB ativas e status do serviço smbd.
* **Leve e Rápido:** Arquitetura otimizada para rodar nativamente na sua infraestrutura, sem dependência pesada de banco de dados para os logs.

### ⚙️ Pré-requisitos
* Ambiente Linux (Debian/Ubuntu) ou FreeBSD.
* Servidor Samba instalado e configurado.
* PHP 8.x
* Servidor Web (Apache/Nginx)
* Banco de Dados (MySQL/MariaDB)

### 🚀 Instalação (Em Breve)
*As instruções de instalação e deploy serão adicionadas em breve.*

### 💼 Suporte Comercial
Este é um software de código aberto (Open-Source). Para instituições que necessitam de **SLA e Suporte Técnico** obrigatório (como exigido por normativas de compliance), oferecemos contratos de suporte comercial. Entre em contato para mais detalhes.

---

## 🤝 Contributing / Contribuindo
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change. / *Pull requests são bem-vindos. Para mudanças maiores, por favor, abra uma "issue" primeiro para discutirmos o que você gostaria de alterar.*

## 📄 License / Licença
This project is licensed under the [GPLv3 License](LICENSE) / *Este projeto está licenciado sob a Licença GPLv3.*

---

## 👨‍💻 Founders / Fundadores
Este projeto foi idealizado e criado por:

| [<img src="https://github.com/VictorWegner.png" width="75px;"/>](https://github.com/VictorWegner) | [<img src="https://github.com/afsdefarias.png" width="75px;"/>](https://github.com/afsdefarias) | [<img src="https://github.com/jporco.png" width="75px;"/>](https://github.com/jporco) |
| :---: | :---: | :---: |
| **Victor Wegner** | **André de Farias** | **JPORCO** |
