# Lab Relator — Sistema Relator de Problemas em Laboratório

Projeto Integrador · TADS 3º Semestre · UniEinstein Limeira · 2026

Sistema web para registro, acompanhamento e gerenciamento de ocorrências
em laboratórios de informática, desenvolvido em PHP 8.2+ com arquitetura
MVC própria, MySQL e Bootstrap 5.3.

## Funcionalidades

- Autenticação com controle de perfis (Gestor, Professor, Técnico)
- Registro e acompanhamento de ocorrências por laboratório
- Monitor de chamados para técnicos e gestores
- Máquina de estados: Não Atendida → Em Edição → Em Atendimento → Encerrada
- Histórico completo de alterações por ocorrência
- Relatórios com exportação CSV e PDF
- Notificações por e-mail via PHPMailer

## Credenciais padrão (apenas para desenvolvimento)

| Perfil    | E-mail                      | Senha       |
|-----------|-----------------------------|-------------|
| Gestor    | admin@unieinstein.edu.br    | Admin@2026  |
| Professor | prof@lab.edu.br             | Prof@2026   |
| Técnico   | tec@lab.edu.br              | Tec@2026    |

> Altere todas as senhas antes de qualquer uso em produção.

## Como rodar localmente

Veja o arquivo completo em: `Documentacao/Como rodar localmente.pdf`

## Tecnologias

PHP 8.2+ · MySQL 8.x · Bootstrap 5.3 · PHPMailer 6.x · Composer 2.x

## Autores

- Adriel Venancio Buccier
- Carlos Victor Pinto Fiuza
- Juan David Moreno
