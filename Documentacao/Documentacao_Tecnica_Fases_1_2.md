# Sistema Relator de Problemas em Laboratorio

Documentacao tecnica viva do projeto apos as Fases 1 e 2.

- PHP: 8.2
- Banco: MySQL 8.x ou MariaDB compativel
- Arquitetura: MVC
- Servidor local usado no desenvolvimento: `http://127.0.0.1:8010`
- Autoload: Composer PSR-4, namespace `App\` apontando para `Lab_relator/app/`

Este documento explica o que cada parte faz e deve ser atualizado sempre que uma nova fase for implementada.

## 1. Visao Geral

O Sistema Relator de Problemas em Laboratorio e uma aplicacao web interna para registrar, acompanhar e gerenciar problemas tecnicos em laboratorios de uma instituicao de ensino.

Perfis do sistema:

- Professor: registra ocorrencias e acompanha os proprios chamados.
- Tecnico: acompanha chamados, assume atendimentos e encerra ocorrencias.
- Gestor: administra cadastros, acompanha todos os chamados e consulta relatorios.

## 2. Arquitetura MVC

O projeto foi organizado no padrao MVC:

- Model: arquivos em `app/Models/`, responsaveis por acesso a dados e SQL via PDO.
- View: arquivos em `views/`, responsaveis apenas por exibir HTML com dados recebidos.
- Controller: arquivos em `app/Controllers/`, responsaveis por orquestrar requisicoes, models e views.

Fluxo de uma requisicao:

1. O navegador acessa uma URL.
2. O servidor envia a requisicao para `index.php`.
3. `index.php` inicia sessao, define timezone, carrega o autoload, registra rotas e chama o roteador.
4. `Router` encontra a rota compativel com URL e metodo HTTP.
5. Middlewares como `auth`, `role:*` e `csrf` rodam antes do controller.
6. O controller chama models quando precisa de dados.
7. O controller chama `render()`, `json()` ou `redirect()`.
8. A view gera HTML para o navegador.

## 3. Mapa de Arquivos

### `Lab_relator/index.php`

Front Controller da aplicacao.

Responsabilidades:

- configura fallback local para sessoes em `storage/sessions`;
- inicia `session_start()`;
- carrega `vendor/autoload.php`;
- define timezone `America/Sao_Paulo`;
- calcula `APP_BASE_PATH` para funcionar em subpasta;
- garante existencia do token CSRF;
- registra rotas GET e POST;
- aciona `$router->dispatch()`.

Regra: nao deve conter regra de negocio. O arquivo deve permanecer limitado a bootstrap e roteamento.

### `Lab_relator/config/database.php`

Arquivo de configuracao do banco.

Ele retorna um array com:

- driver;
- host;
- porta;
- database;
- usuario;
- senha;
- charset;
- opcoes PDO.

As credenciais podem vir de variaveis de ambiente:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_CHARSET`

Observacao: no ambiente atual, o MySQL retornou `Access denied for user 'root'@'localhost' (using password: NO)`. Para login real funcionar, ajuste este arquivo ou configure as variaveis de ambiente e importe `database/schema_fase2.sql`.

## 4. Core

### `app/Core/Router.php`

Roteador dinamico do sistema.

Faz:

- cadastra rotas por metodo HTTP (`get`, `post`, `put`, `delete`);
- transforma rotas com parametros, como `/auth/resetar/{token}`, em regex;
- extrai parametros dinamicos e envia ao controller;
- executa middlewares encadeados com `->middleware(...)`;
- suporta override de metodo via `_method`;
- exibe paginas de erro com `abort(403)`, `abort(404)` ou `abort(500)`.

Middlewares reconhecidos atualmente:

- `auth`
- `role:gestor`
- `role:professor,gestor`
- `csrf`

### `app/Core/Database.php`

Singleton de conexao PDO.

Faz:

- le `config/database.php`;
- monta DSN MySQL com `utf8mb4`;
- cria uma unica instancia PDO por requisicao;
- usa `PDO::ERRMODE_EXCEPTION`;
- usa `PDO::FETCH_ASSOC`;
- desativa `PDO::ATTR_EMULATE_PREPARES`;
- registra erro tecnico em `error_log()`;
- lanca erro generico para nao vazar credenciais.

### `app/Core/Middleware/AuthMiddleware.php`

Middleware de autenticacao e autorizacao.

Faz:

- verifica se existe usuario logado via `SessionHelper`;
- se nao houver sessao, grava flash message e redireciona para `/auth/login`;
- verifica perfil quando a rota usa `role:*`;
- se perfil nao bater, retorna erro 403.

### `app/Core/Middleware/CsrfMiddleware.php`

Middleware de protecao CSRF.

Faz:

- roda em requisicoes POST/PUT/PATCH/DELETE;
- procura token em `csrf_token`, `_token` ou header `X-CSRF-TOKEN`;
- valida com `hash_equals()`;
- se falhar, grava flash message e redireciona para a pagina anterior ou login.

Na Fase 2, ele esta aplicado nos POSTs de autenticacao:

- `/login`
- `/auth/login`
- `/auth/recuperar`
- `/auth/resetar/{token}`

## 5. Helpers

### `app/Helpers/Csrf.php`

Helper do token CSRF.

Metodos:

- `token()`: cria ou retorna token da sessao;
- `validate($token)`: valida token recebido;
- `rotate()`: gera novo token.

O token e guardado em `$_SESSION['csrf_token']`.

### `app/Helpers/SessionHelper.php`

Centraliza acesso a sessao.

Metodos principais:

- `login($user)`: regenera ID de sessao e grava usuario;
- `logout()`: limpa sessao, cookie e destroi a sessao;
- `user()`: retorna usuario logado ou `null`;
- `id()`: retorna ID do usuario;
- `role()`: retorna perfil;
- `isAuthenticated()`: indica se ha usuario logado;
- `flash($type, $message)`: grava mensagem de uso unico;
- `pullFlash()`: le e remove flash message.

Tambem mantem chaves legadas usadas pelas views antigas:

- `id_usuario`
- `nome_usuario`
- `email_usuario`
- `tipo_usuario`

## 6. Controllers

### `app/Controllers/BaseController.php`

Classe pai dos controllers.

Metodos:

- `render($view, $data, $useLayout)`;
- `json($payload, $status)`;
- `redirect($path)`;
- `requireLogin()`;
- `requireRole(...$roles)`;
- `abort($status)`.

`render()` usa `extract()` para transformar o array de dados em variaveis disponiveis na view.

### `app/Controllers/AuthController.php`

Responsavel por autenticacao real da Fase 2.

Rotas principais:

- `GET /auth/login` -> `login()`;
- `POST /auth/login` -> `processLogin()`;
- `GET /auth/logout` -> `logout()`;
- `GET /auth/recuperar` -> `recuperar()`;
- `POST /auth/recuperar` -> `processRecuperar()`;
- `GET /auth/resetar/{token}` -> `resetar()`;
- `POST /auth/resetar/{token}` -> `processResetar()`.

O login agora:

- normaliza e valida e-mail;
- valida tamanho minimo da senha;
- consulta usuario ativo no banco via `UsuarioModel`;
- usa `password_verify()`;
- registra tentativa em `login_attempts`;
- bloqueia apos 5 falhas em janela de 15 minutos por e-mail ou IP;
- usa `SessionHelper::login()`;
- rotaciona CSRF apos login bem-sucedido.

Recuperacao de senha:

- gera token seguro com `random_bytes()`;
- armazena apenas `hash('sha256', $token)`;
- expira tokens anteriores do mesmo usuario;
- cria token valido por 1 hora;
- em modo dev, mostra o link na tela e registra em `error_log()`;
- ainda nao envia e-mail real. Isso fica para a fase de notificacoes.

### `app/Controllers/DashboardController.php`

Controla a pagina inicial apos login.

Faz:

- le usuario logado via `SessionHelper`;
- consulta dados de usuarios e ocorrencias;
- monta cards diferentes por perfil;
- trata falhas de banco com mensagem amigavel no dashboard.

Observacao: se a tabela `ocorrencia` ainda nao existir, `DashboardModel` retorna contadores zerados.

### `app/Controllers/PageController.php`

Controller temporario para manter views existentes acessiveis enquanto CRUDs reais nao foram implementados.

Ele apenas renderiza paginas atuais:

- laboratorios;
- equipamentos;
- professores;
- tecnicos;
- ocorrencias;
- monitor;
- relatorios;
- tipos de problema.

Na Fase 3, cada modulo deve ganhar controller e model proprios.

## 7. Models

### `app/Models/BaseModel.php`

Classe pai dos models.

Fornece:

- `findAll($conditions, $orderBy)`;
- `findById($id)`;
- `insert($data)`;
- `update($id, $data)`;
- `delete($id)`;
- `query($sql, $params)`.

Todos usam prepared statements.

### `app/Models/UsuarioModel.php`

Representa a tabela `usuarios`.

Metodos especificos:

- `findActiveByEmail($email)`;
- `countByPerfil()`;
- `countActive()`;
- `updatePassword($id, $plainPassword)`.

### `app/Models/LoginAttemptModel.php`

Representa a tabela `login_attempts`.

Modelo da Fase 2:

- cada tentativa e registrada como uma linha;
- `success = 0` indica falha;
- `success = 1` indica sucesso;
- bloqueio e calculado por consulta, nao por coluna `bloqueado_ate`.

Regra atual:

- 5 falhas em 15 minutos;
- verificacao por e-mail ou IP;
- retorno de tempo restante aproximado.

### `app/Models/PasswordResetModel.php`

Representa a tabela `password_resets`.

Faz:

- cria token de recuperacao;
- salva apenas hash SHA-256 do token;
- expira tokens abertos anteriores do usuario;
- busca token valido;
- marca token como usado;
- conta tokens ativos.

### `app/Models/DashboardModel.php`

Model auxiliar para dados do dashboard.

Faz:

- verifica se uma tabela existe;
- calcula estatisticas de ocorrencias por perfil;
- retorna zeros se a tabela `ocorrencia` ainda nao existir.

## 8. Views Principais

### `views/layouts/header.php`

Layout principal.

Faz:

- prepara dados do usuario logado;
- monta itens da sidebar por perfil;
- inclui CSS e bibliotecas externas;
- renderiza topbar;
- exibe flash messages;
- abre o `<main>`.

### `views/layouts/footer.php`

Fecha o layout e inclui scripts.

### `views/layouts/partials/sidebar_content.php`

Conteudo do menu lateral.

Criado na Fase 1 para corrigir o include ausente que quebrava a sidebar.

### `views/auth/login.php`

Formulario de login.

Inclui:

- campo oculto `csrf_token`;
- e-mail;
- senha;
- link para recuperacao;
- exibicao de flash message;
- exibicao de erro de autenticacao.

### `views/auth/recuperar.php`

Formulario para solicitar recuperacao de senha.

Na Fase 2, se o e-mail existir, mostra o link local de teste.

### `views/auth/resetar.php`

Formulario para definir nova senha a partir de um token valido.

### `views/dashboard/index.php`

Dashboard por perfil.

- Gestor: usuarios ativos, professores, tecnicos, falhas de login.
- Professor: suas ocorrencias, quando tabela existir.
- Tecnico: chamados visiveis, quando tabela existir.
- Todos: painel de contexto e tokens de recuperacao ativos.

### `views/errors/403.php`, `404.php`, `500.php`

Paginas de erro HTTP.

## 9. Banco de Dados

### `database/schema_fase1.sql`

Cria tabela `usuarios` e seed inicial.

Usuarios seed:

- `gestor@unieinstein.edu.br` / `Gestor@123`
- `professor@unieinstein.edu.br` / `Professor@123`
- `tecnico@unieinstein.edu.br` / `Tecnico@123`

### `database/schema_fase2.sql`

Cria ou garante:

- `usuarios`;
- `login_attempts`;
- `password_resets`;
- seed de usuarios.

#### Tabela `usuarios`

Colunas atuais:

- `id`;
- `nome`;
- `email`;
- `senha`;
- `perfil`;
- `ativo`;
- `created_at`.

#### Tabela `login_attempts`

Colunas atuais:

- `id`;
- `email`;
- `ip_address`;
- `user_agent`;
- `success`;
- `attempted_at`.

#### Tabela `password_resets`

Colunas atuais:

- `id`;
- `usuario_id`;
- `email`;
- `token_hash`;
- `expires_at`;
- `used_at`;
- `ip_address`;
- `created_at`.

## 10. Seguranca Implementada

- Bcrypt para senhas.
- `password_verify()` no login.
- Prepared statements em todos os models.
- CSRF nas rotas POST de auth.
- `session_regenerate_id(true)` no login.
- Flash messages de uso unico.
- Rate limiting de login.
- Tokens de recuperacao armazenados como hash SHA-256.
- `htmlspecialchars()` nas views novas.
- Fallback seguro para arquivos de sessao em `storage/sessions`.

## 11. Fluxos

### Login

1. Usuario acessa `/auth/login`.
2. View exibe formulario com token CSRF.
3. POST passa pelo `CsrfMiddleware`.
4. `AuthController::processLogin()` valida email e senha.
5. `LoginAttemptModel` verifica bloqueio.
6. `UsuarioModel` busca usuario ativo.
7. `password_verify()` valida senha.
8. Sessao e regenerada.
9. Usuario vai para `/dashboard`.

### Recuperacao de Senha

1. Usuario acessa `/auth/recuperar`.
2. Informa e-mail.
3. POST passa pelo CSRF.
4. Se e-mail existir, `PasswordResetModel` cria token.
5. Link e exibido em modo dev.
6. Usuario acessa `/auth/resetar/{token}`.
7. Token e validado.
8. Nova senha e salva com bcrypt.
9. Token e marcado como usado.
10. Usuario volta ao login.

### Dashboard

1. Rota `/dashboard` exige `auth`.
2. Controller identifica perfil.
3. Dados sao consultados no banco quando disponiveis.
4. View renderiza cards por perfil.

## 12. Como Rodar Localmente

1. Configure o banco em `Lab_relator/config/database.php`.
2. Importe `Lab_relator/database/schema_fase2.sql`.
3. Rode:

```bash
cd Lab_relator
composer dump-autoload
php -S 127.0.0.1:8010 index.php
```

4. Acesse:

```text
http://127.0.0.1:8010/auth/login
```

## 13. Validacoes Feitas

Apos a Fase 2:

- `php -l` passou em todos os arquivos PHP fora de `vendor`;
- `composer dump-autoload` executou com sucesso;
- login, recuperacao e dashboard carregam no navegador;
- login real depende do MySQL configurado e do schema importado.

## 14. Roadmap

### Fase 1 - Base MVC

Concluida:

- front controller;
- router;
- database singleton;
- auth middleware;
- base controller;
- base model;
- sidebar corrigida;
- paginas de erro.

### Fase 2 - Autenticacao Real

Concluida:

- login via banco;
- bcrypt;
- CSRF;
- rate limiting;
- session helper;
- recuperacao com token;
- dashboard por perfil.

### Fase 3 - CRUDs de Cadastro

Proximo alvo:

- `LaboratorioController` e `LaboratorioModel`;
- `EquipamentoController` e `EquipamentoModel`;
- controllers/models para professores e tecnicos;
- `TipoProblemaController`;
- validacao backend dos formularios.

### Fase 4 - Ocorrencias

Previsto:

- abertura de ocorrencia por professor;
- atendimento por tecnico;
- encerramento;
- historico;
- monitor real.

### Fase 5 - Notificacoes e Relatorios

Previsto:

- PHPMailer real;
- e-mail de recuperacao;
- alertas de ocorrencia;
- exportacao CSV/PDF;
- filtros gerenciais.
