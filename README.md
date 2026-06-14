# Lab Relator - Sistema Relator de Problemas em Laboratorio

Projeto Integrador - TADS - UniEinstein Limeira - 2026

Aplicacao web academica para registro, acompanhamento e gestao de ocorrencias em laboratorios de informatica. O sistema usa PHP com MVC proprio, PDO, MySQL/MariaDB, Bootstrap 5.3 e PHPMailer.

## Stack

- PHP 8.2+ conforme ambiente local atual
- Composer 2.x
- MySQL 8.x ou MariaDB compativel
- Bootstrap 5.3
- PHPMailer 6.x

Observacao: o projeto esta implementado para MySQL/MariaDB. Nao esta pronto para PostgreSQL sem migrar schema e queries especificas de MySQL.

## Modulos Implementados

- Autenticacao com perfis: gestor, professor e tecnico
- CSRF em rotas POST
- Rate limit de login
- Recuperacao de senha com token e MailService
- Cadastros de laboratorios, equipamentos, tipos de problema, professores e tecnicos
- Registro e acompanhamento de ocorrencias
- Monitor de chamados para tecnico e gestor
- Historico de ocorrencias
- Relatorios com exportacao CSV e HTML de impressao
- Notificacoes por e-mail via PHPMailer

## Banco de Dados

Schema principal:

```text
Lab_relator/database/schema.sql
```

Tabelas principais:

- `usuarios`
- `login_attempts`
- `password_resets`
- `laboratorios`
- `equipamentos`
- `tipos_problema`
- `ocorrencia`
- `ocorrencia_historico`

Seed atual do schema:

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Gestor | admin@unieinstein.edu.br | Admin@2026 |

Professores e tecnicos podem ser cadastrados pelo proprio sistema apos login como gestor.

## Configuracao Local

O arquivo `Lab_relator/config/database.php` aceita variaveis de ambiente:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME` ou `DB_DATABASE`
- `DB_USER` ou `DB_USERNAME`
- `DB_PASS` ou `DB_PASSWORD`
- `DB_CHARSET`

Opcionalmente, crie o arquivo local abaixo. Ele e ignorado pelo Git:

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'lab_relator',
    'username' => 'root',
    'password' => 'sua_senha_local',
    'charset' => 'utf8mb4',
];
```

Salvar como:

```text
Lab_relator/config/database.local.php
```

## Como Rodar Localmente

1. Entre na pasta da aplicacao:

```powershell
cd "C:\Users\David\OneDrive\Área de Trabalho\Codes\Projeto integrador\Projeto-integrador-1-TADS\Lab_relator"
```

2. Instale as dependencias:

```powershell
composer install
```

3. Ligue o MySQL no XAMPP, Laragon, WampServer ou servico local equivalente.

4. Importe o schema:

```powershell
mysql -u root -p < database/schema.sql
```

5. Teste a conexao:

```powershell
php config/database.php
```

Resultado esperado:

```text
[OK] Conexao com banco estabelecida.
```

6. Suba o servidor embutido do PHP:

```powershell
php -S 127.0.0.1:8000 index.php
```

7. Acesse:

```text
http://127.0.0.1:8000/auth/login
```

## Rotas Principais

- `GET /auth/login`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /dashboard`
- `GET /laboratorio`
- `GET /equipamento`
- `GET /usuario/professor`
- `GET /usuario/tecnico`
- `GET /tipo-problema`
- `GET /ocorrencia`
- `GET /ocorrencia/criar`
- `GET /monitor`
- `GET /relatorio`
- `GET /relatorio/exportar-csv`
- `GET /relatorio/exportar-pdf`

Tambem existem aliases de cadastro:

- `GET /professor`
- `GET /tecnico`

## Documentacao Tecnica

Documento principal:

```text
Documentacao/Documentacao_Tecnica_Fases_1_2.md
```

Apesar do nome historico do arquivo, ele foi atualizado para refletir o estado atual do projeto.

## Autores

- Adriel Venancio Buccier
- Carlos Victor Pinto Fiuza
- Juan David Moreno
