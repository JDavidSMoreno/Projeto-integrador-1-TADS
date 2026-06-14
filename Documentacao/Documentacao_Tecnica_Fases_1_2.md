# Lab Relator - Documentacao Tecnica Atualizada

Documento tecnico vivo do Sistema Relator de Problemas em Laboratorio.

Ultima revisao: 2026-06-14.

## 1. Escopo Atual

O projeto deixou de estar limitado as fases 1 e 2. O codigo atual ja possui:

- autenticacao com controle de sessao;
- rate limit de login;
- recuperacao de senha;
- cadastros administrativos;
- registro e acompanhamento de ocorrencias;
- monitor de chamados;
- historico de status;
- relatorios com exportacao CSV e HTML de impressao;
- servico de e-mail com PHPMailer.

O nome deste arquivo foi mantido por historico do repositorio, mas o conteudo agora descreve o estado atual do sistema.

## 2. Stack

- PHP 8.2+ no ambiente local atual.
- Composer com autoload PSR-4.
- MySQL 8.x ou MariaDB compativel.
- PDO com prepared statements.
- Bootstrap 5.3.
- PHPMailer 6.x.

O projeto nao esta pronto para PostgreSQL. O schema e algumas consultas usam recursos especificos de MySQL, como `AUTO_INCREMENT`, `ENUM`, `FIELD()`, `TIMESTAMPDIFF()` e `ON UPDATE CURRENT_TIMESTAMP`.

## 3. Arquitetura

Padrao MVC proprio:

- `Lab_relator/index.php`: front controller, bootstrap e registro de rotas.
- `Lab_relator/app/Core`: roteador, banco e middlewares.
- `Lab_relator/app/Controllers`: controllers HTTP.
- `Lab_relator/app/Models`: acesso a dados com PDO.
- `Lab_relator/app/Views` nao existe; as views ficam em `Lab_relator/views`.
- `Lab_relator/app/Helpers`: utilitarios de sessao, CSRF e validacao.
- `Lab_relator/app/Services`: servicos externos, como e-mail.

Fluxo padrao:

1. Browser chama uma URL.
2. `index.php` inicia sessao, configura ambiente, carrega Composer e registra rotas.
3. `Router` identifica metodo e caminho.
4. Middlewares `auth`, `role:*` e `csrf` executam quando configurados.
5. Controller valida permissao, le entrada, chama models e renderiza view, JSON ou redirect.
6. Views escapam dados com `htmlspecialchars`.

## 4. Configuracao

### Banco

Arquivo:

```text
Lab_relator/config/database.php
```

Aceita variaveis:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME` ou `DB_DATABASE`
- `DB_USER` ou `DB_USERNAME`
- `DB_PASS` ou `DB_PASSWORD`
- `DB_CHARSET`

Tambem aceita configuracao local ignorada pelo Git:

```text
Lab_relator/config/database.local.php
```

Exemplo:

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

Teste:

```powershell
php Lab_relator/config/database.php
```

### E-mail

Arquivo:

```text
Lab_relator/config/mail.php
```

Variaveis:

- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USER`
- `MAIL_PASS`
- `MAIL_FROM`
- `MAIL_NAME`

Se SMTP nao estiver configurado, o reset de senha cai no fallback de desenvolvimento em `storage/logs/reset.log`.

## 5. Banco de Dados

Schema principal:

```text
Lab_relator/database/schema.sql
```

Tabelas:

- `usuarios`
- `login_attempts`
- `password_resets`
- `laboratorios`
- `equipamentos`
- `tipos_problema`
- `ocorrencia`
- `ocorrencia_historico`

Seed atual:

- Gestor: `admin@unieinstein.edu.br`
- Senha: `Admin@2026`

Professores e tecnicos sao cadastrados pelo gestor na propria aplicacao.

## 6. Controllers

### AuthController

Rotas:

- `GET /auth/login`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/recuperar`
- `POST /auth/recuperar`
- `GET /auth/resetar/{token}`
- `POST /auth/resetar/{token}`

Regras:

- login por usuario ativo;
- senha com `password_verify`;
- rate limit por e-mail/IP;
- logout apenas por POST com CSRF;
- recuperacao gera token e chama `MailService`;
- link de reset nao aparece no HTML.

### DashboardController

Renderiza dashboard por perfil e consulta estatisticas em `DashboardModel`.

### LaboratorioController

CRUD administrativo de laboratorios.

### EquipamentoController

CRUD administrativo de equipamentos e endpoint auxiliar:

- `GET /equipamento/por-laboratorio`

### TipoProblemaController

CRUD administrativo de tipos de problema.

### ProfessorController e TecnicoController

Herdam de `UsuarioPerfilController`.

Rotas canonicas:

- `/usuario/professor`
- `/usuario/tecnico`

Aliases:

- `/professor`
- `/tecnico`

Regras:

- somente gestor acessa;
- cria usuario com perfil fixo;
- edita dados e senha opcional;
- ativa/desativa usuarios;
- listas exibem contagens reais de ocorrencias.

### OcorrenciaController

Rotas principais:

- `GET /ocorrencia`
- `GET /ocorrencia/criar`
- `POST /ocorrencia/registrar`
- `GET /ocorrencia/ver/{id}`
- `GET /ocorrencia/editar/{id}`
- `POST /ocorrencia/atualizar/{id}`
- `POST /ocorrencia/cancelar/{id}`

Regras:

- professor cria ocorrencia;
- `id_professor` vem da sessao;
- professor so ve suas proprias ocorrencias;
- criacao gera historico;
- nova ocorrencia tenta notificar gestores por e-mail.

### MonitorController

Rotas:

- `GET /monitor`
- `GET /monitor/historico/{id}`
- `POST /monitor/atualizar-status`

Regras:

- tecnico e gestor acessam;
- mudanca de status passa pela maquina de estados de `OcorrenciaModel`;
- encerramento tenta notificar professor por e-mail.

### RelatorioController

Rotas:

- `GET /relatorio`
- `GET /relatorio/exportar-csv`
- `GET /relatorio/exportar-pdf`

Regras:

- somente gestor acessa;
- filtros por data, status, laboratorio, tipo e tecnico;
- CSV usa `text/csv`;
- PDF e HTML formatado para impressao.

## 7. Models

- `BaseModel`: CRUD generico e `query` com PDO.
- `UsuarioModel`: usuarios, perfis, senha e contagens por ocorrencia.
- `LoginAttemptModel`: tentativas e bloqueio de login.
- `PasswordResetModel`: tokens de recuperacao.
- `DashboardModel`: estatisticas por perfil.
- `LaboratorioModel`: laboratorios.
- `EquipamentoModel`: equipamentos.
- `TipoProblemaModel`: tipos de problema.
- `OcorrenciaModel`: ocorrencias, historico e maquina de estados.
- `RelatorioModel`: agregacoes e listagem para relatorios.

## 8. Maquina de Estados

Status validos:

- `Nao Atendida`
- `Em Edicao`
- `Em Atendimento`
- `Encerrada`

Transicoes implementadas:

- `Nao Atendida -> Em Edicao`: professor dono.
- `Nao Atendida -> Em Atendimento`: tecnico ou gestor.
- `Em Edicao -> Nao Atendida`: professor dono.
- `Em Edicao -> Em Atendimento`: tecnico ou gestor.
- `Em Atendimento -> Encerrada`: tecnico ou gestor.
- `Em Atendimento -> Nao Atendida`: gestor.

Ao iniciar atendimento, `id_tecnico` recebe o ID do tecnico ou gestor que assumiu. Ao encerrar, `data_encerramento` e preenchida. Ao reabrir para `Nao Atendida`, `data_encerramento` e limpa.

## 9. Seguranca

Implementado:

- bcrypt para senhas;
- `password_verify`;
- CSRF em POSTs;
- logout por POST;
- cookie de sessao com HttpOnly e SameSite;
- `session_regenerate_id(true)` no login;
- prepared statements nos models;
- validacao de identificadores nos helpers genericos;
- token de reset salvo como hash;
- output HTML escapado nas views principais;
- autorizacao por `$_SESSION` e middlewares.

Pontos operacionais:

- configurar SMTP real para envio fora de desenvolvimento;
- nao versionar `database.local.php`;
- trocar senhas seed antes de ambiente real.

## 10. Como Rodar

```powershell
cd "C:\Users\David\OneDrive\Área de Trabalho\Codes\Projeto integrador\Projeto-integrador-1-TADS\Lab_relator"
composer install
mysql -u root -p < database/schema.sql
php config/database.php
php -S 127.0.0.1:8000 index.php
```

Acesso:

```text
http://127.0.0.1:8000/auth/login
```

Credencial seed:

```text
admin@unieinstein.edu.br
Admin@2026
```

## 11. Discrepancias Corrigidas Nesta Revisao

- README apontava para PDF de instalacao inexistente.
- README listava professor e tecnico como seeds, mas o schema atual cria apenas gestor.
- Documento tecnico dizia que logout era GET; agora e POST com CSRF.
- Documento tecnico dizia que reset exibia link; agora usa `MailService` e fallback em log.
- Documento tecnico referenciava `schema_fase2.sql`; o schema principal atual e `database/schema.sql`.
- Documento tecnico tratava cadastros, ocorrencias, monitor, relatorios e notificacoes como roadmap, mas eles ja existem no codigo.
- Documento tecnico nao citava aliases `/professor` e `/tecnico`.
- Documentacao nao explicava `database.local.php`, apesar de o codigo aceitar esse arquivo.

## 12. Verificacoes Recomendadas

```powershell
Get-ChildItem -Path . -Recurse -Filter *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\' } |
  ForEach-Object { php -l $_.FullName } |
  Select-String -Pattern 'No syntax errors' -NotMatch
```

Resultado esperado: nenhuma linha.

```powershell
php Lab_relator/config/database.php
```

Resultado esperado: `[OK] Conexao com banco estabelecida.`
