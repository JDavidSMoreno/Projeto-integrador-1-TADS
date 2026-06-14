const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, HeadingLevel, BorderStyle, WidthType, ShadingType,
  VerticalAlign, PageBreak, LevelFormat, ExternalHyperlink
} = require('docx');
const path = require('path');
const fs = require('fs');

const OUTPUT_FILE = path.join(__dirname, 'Lab_Relator_Documentacao_Publica.docx');

// ── Cores do sistema ──────────────────────────────────────
const COR_AZUL      = '1B4F8A';
const COR_AZUL_CLARO= '2E75B6';
const COR_CINZA_ESC = '404040';
const COR_CINZA_MED = '6B7280';
const COR_CINZA_CLR = 'F3F4F6';
const COR_VERDE     = '166534';
const COR_VERDE_BG  = 'DCFCE7';
const COR_AMARELO   = '854D0E';
const COR_AMARELO_BG= 'FEF9C3';
const COR_AZUL_BG   = 'DBEAFE';
const COR_ROXO_BG   = 'F3E8FF';
const COR_ROXO      = '6B21A8';
const COR_LARANJA   = '9A3412';
const COR_LARANJA_BG= 'FFEDD5';
const BRANCO        = 'FFFFFF';

const LINE = { style: BorderStyle.SINGLE, size: 1, color: 'CCCCCC' };
const NO_BORDER = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const BORDERS = { top: LINE, bottom: LINE, left: LINE, right: LINE };
const NO_BORDERS = { top: NO_BORDER, bottom: NO_BORDER, left: NO_BORDER, right: NO_BORDER };
const CELL_MARGIN = { top: 120, bottom: 120, left: 160, right: 160 };

// ── Helpers ───────────────────────────────────────────────
const h1 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_1,
  spacing: { before: 400, after: 200 },
  border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: COR_AZUL_CLARO, space: 4 } },
  children: [new TextRun({ text, font: 'Arial', size: 32, bold: true, color: COR_AZUL })]
});

const h2 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_2,
  spacing: { before: 300, after: 160 },
  children: [new TextRun({ text, font: 'Arial', size: 26, bold: true, color: COR_AZUL_CLARO })]
});

const h3 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_3,
  spacing: { before: 220, after: 120 },
  children: [new TextRun({ text, font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC })]
});

const p = (text, opts = {}) => new Paragraph({
  spacing: { before: 80, after: 140 },
  alignment: opts.justify ? AlignmentType.BOTH : AlignmentType.LEFT,
  children: [new TextRun({
    text,
    font: 'Arial',
    size: 22,
    color: opts.color || COR_CINZA_ESC,
    bold: opts.bold || false,
    italics: opts.italic || false,
  })]
});

const pMixed = (runs) => new Paragraph({
  spacing: { before: 80, after: 140 },
  children: runs.map(r => new TextRun({
    text: r.text,
    font: 'Arial',
    size: 22,
    bold: r.bold || false,
    italics: r.italic || false,
    color: r.color || COR_CINZA_ESC,
  }))
});

const bullet = (text, bold_prefix = null) => new Paragraph({
  spacing: { before: 60, after: 60 },
  numbering: { reference: 'bullets', level: 0 },
  children: bold_prefix
    ? [
        new TextRun({ text: bold_prefix + ' ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
        new TextRun({ text, font: 'Arial', size: 22, color: COR_CINZA_ESC })
      ]
    : [new TextRun({ text, font: 'Arial', size: 22, color: COR_CINZA_ESC })]
});

const spacer = (pt = 160) => new Paragraph({
  spacing: { before: pt, after: 0 },
  children: [new TextRun({ text: '' })]
});

const pageBreak = () => new Paragraph({
  children: [new TextRun({ break: 1 })]
});

// ── Célula de tabela ──────────────────────────────────────
const cell = (children, opts = {}) => new TableCell({
  borders: opts.borders || BORDERS,
  width: opts.width ? { size: opts.width, type: WidthType.DXA } : undefined,
  shading: opts.bg ? { fill: opts.bg, type: ShadingType.CLEAR } : undefined,
  margins: opts.margins || CELL_MARGIN,
  verticalAlign: opts.vAlign || VerticalAlign.TOP,
  columnSpan: opts.span || 1,
  children: Array.isArray(children) ? children : [children]
});

const cellText = (text, opts = {}) => cell(
  [new Paragraph({
    alignment: opts.center ? AlignmentType.CENTER : AlignmentType.LEFT,
    children: [new TextRun({
      text,
      font: 'Arial',
      size: opts.size || 20,
      bold: opts.bold || false,
      color: opts.color || COR_CINZA_ESC,
    })]
  })],
  opts
);

const headerCell = (text, width, bg = COR_AZUL) => cellText(text, {
  bold: true, center: true, bg, color: BRANCO, width, size: 20,
  borders: BORDERS
});

// ── Card colorido (bloco de destaque) ────────────────────
const colorCard = (title, lines, bg, titleColor, borderColor) => {
  const cardBorder = {
    top: { style: BorderStyle.SINGLE, size: 6, color: borderColor },
    bottom: { style: BorderStyle.SINGLE, size: 1, color: 'DDDDDD' },
    left: { style: BorderStyle.SINGLE, size: 6, color: borderColor },
    right: { style: BorderStyle.SINGLE, size: 1, color: 'DDDDDD' },
  };
  return new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [9360],
    rows: [new TableRow({
      children: [cell(
        [
          new Paragraph({
            spacing: { before: 60, after: 80 },
            children: [new TextRun({ text: title, font: 'Arial', size: 22, bold: true, color: titleColor })]
          }),
          ...lines.map(l => new Paragraph({
            spacing: { before: 40, after: 40 },
            children: [new TextRun({ text: l, font: 'Arial', size: 20, color: COR_CINZA_ESC })]
          }))
        ],
        { bg, borders: cardBorder, margins: { top: 120, bottom: 120, left: 180, right: 180 } }
      )]
    })]
  });
};

// ── Tabela de credenciais ─────────────────────────────────
const credTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [2340, 3600, 3420],
  rows: [
    new TableRow({
      children: [
        headerCell('Perfil', 2340),
        headerCell('E-mail', 3600),
        headerCell('Senha', 3420),
      ]
    }),
    new TableRow({
      children: [
        cellText('Gestor', { bold: true, bg: COR_AZUL_BG, color: '1e3a5f', width: 2340 }),
        cellText('admin@unieinstein.edu.br', { width: 3600 }),
        cellText('Admin@2026', { width: 3420, bold: true }),
      ]
    }),
    new TableRow({
      children: [
        cellText('Professor', { bold: true, bg: COR_VERDE_BG, color: COR_VERDE, width: 2340 }),
        cellText('prof@lab.edu.br', { width: 3600 }),
        cellText('Prof@2026', { width: 3420, bold: true }),
      ]
    }),
    new TableRow({
      children: [
        cellText('Técnico', { bold: true, bg: COR_LARANJA_BG, color: COR_LARANJA, width: 2340 }),
        cellText('tec@lab.edu.br', { width: 3600 }),
        cellText('Tec@2026', { width: 3420, bold: true }),
      ]
    }),
  ]
});

// ── Tabela de status ──────────────────────────────────────
const statusTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [2340, 3120, 3900],
  rows: [
    new TableRow({ children: [
      headerCell('Status', 2340),
      headerCell('Quem pode definir', 3120),
      headerCell('Significado', 3900),
    ]}),
    new TableRow({ children: [
      cellText('Não Atendida', { bg: 'F1F5F9', bold: true, color: '475569', width: 2340 }),
      cellText('Sistema (automático ao criar)', { width: 3120 }),
      cellText('Chamado aberto, aguardando técnico.', { width: 3900 }),
    ]}),
    new TableRow({ children: [
      cellText('Em Edição', { bg: COR_AMARELO_BG, bold: true, color: COR_AMARELO, width: 2340 }),
      cellText('Professor (dono da ocorrência)', { width: 3120 }),
      cellText('Professor está revisando ou complementando o chamado.', { width: 3900 }),
    ]}),
    new TableRow({ children: [
      cellText('Em Atendimento', { bg: COR_AZUL_BG, bold: true, color: '1D4ED8', width: 2340 }),
      cellText('Técnico ou Gestor', { width: 3120 }),
      cellText('Técnico foi atribuído e está trabalhando na resolução.', { width: 3900 }),
    ]}),
    new TableRow({ children: [
      cellText('Encerrada', { bg: COR_VERDE_BG, bold: true, color: COR_VERDE, width: 2340 }),
      cellText('Técnico ou Gestor', { width: 3120 }),
      cellText('Problema resolvido. Chamado finalizado.', { width: 3900 }),
    ]}),
  ]
});

// ── Tabela de módulos ─────────────────────────────────────
const modulosTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [2200, 1800, 5360],
  rows: [
    new TableRow({ children: [
      headerCell('Módulo', 2200),
      headerCell('Acesso', 1800),
      headerCell('O que faz', 5360),
    ]}),
    ...[
      ['Autenticação', 'Todos', 'Login com e-mail e senha, recuperação de senha por e-mail, bloqueio após 5 tentativas falhas em 15 minutos.'],
      ['Dashboard', 'Todos', 'Painel inicial personalizado por perfil, com contadores de ocorrências por status e atalhos rápidos.'],
      ['Laboratórios', 'Gestor', 'Cadastro, edição, ativação e desativação dos laboratórios da instituição.'],
      ['Equipamentos', 'Gestor', 'Cadastro de equipamentos vinculados a cada laboratório, com número de série e descrição.'],
      ['Tipos de Problema', 'Gestor', 'Categorias de problemas disponíveis para seleção ao registrar uma ocorrência.'],
      ['Professores / Técnicos', 'Gestor', 'Cadastro e gerenciamento dos usuários do sistema por perfil.'],
      ['Ocorrências', 'Professor / Gestor', 'Abertura, acompanhamento, edição e cancelamento de chamados. Professor vê apenas os próprios.'],
      ['Monitor de Chamados', 'Técnico / Gestor', 'Visão completa de todos os chamados abertos, com ação de iniciar atendimento e encerrar.'],
      ['Relatórios', 'Gestor', 'Estatísticas por status, tipo, laboratório e tempo médio de resolução. Exportação em CSV e PDF.'],
      ['Notificações por E-mail', 'Automático', 'E-mail enviado ao gestor ao abrir chamado; e-mail ao professor ao encerrar.'],
    ].map(([mod, acesso, desc]) => new TableRow({ children: [
      cellText(mod, { bold: true, width: 2200, bg: COR_CINZA_CLR }),
      cellText(acesso, { width: 1800, color: COR_CINZA_MED }),
      cellText(desc, { width: 5360 }),
    ]}))
  ]
});

// ── Tabela de tecnologias ─────────────────────────────────
const techTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [2600, 1800, 4960],
  rows: [
    new TableRow({ children: [
      headerCell('Tecnologia', 2600),
      headerCell('Versão', 1800),
      headerCell('Função no projeto', 4960),
    ]}),
    ...[
      ['PHP', '8.2+', 'Linguagem principal do backend. Toda a lógica de negócio, validação e acesso ao banco.'],
      ['MySQL / MariaDB', '8.x', 'Banco de dados relacional. Armazena usuários, laboratórios, ocorrências e histórico.'],
      ['PDO', 'nativo PHP', 'Abstração de banco de dados. Garante prepared statements e proteção contra SQL Injection.'],
      ['Bootstrap', '5.3', 'Framework CSS. Interface responsiva, componentes visuais e layout adaptativo.'],
      ['Bootstrap Icons', '1.x', 'Biblioteca de ícones SVG integrada ao Bootstrap para uso nas interfaces.'],
      ['PHPMailer', '6.x', 'Envio de e-mails via SMTP. Notificações de abertura e encerramento de chamados.'],
      ['Composer', '2.x', 'Gerenciador de dependências PHP. Instala e atualiza bibliotecas externas.'],
      ['Apache / XAMPP', '2.4', 'Servidor web local. Processa requisições HTTP e serve a aplicação PHP.'],
      ['Git + GitHub', '2.x', 'Controle de versão e repositório remoto. Gerencia o histórico do código.'],
    ].map(([tech, ver, func]) => new TableRow({ children: [
      cellText(tech, { bold: true, width: 2600, bg: COR_CINZA_CLR }),
      cellText(ver, { width: 1800, color: COR_CINZA_MED }),
      cellText(func, { width: 4960 }),
    ]}))
  ]
});

// ── Tabela de estrutura de pastas ─────────────────────────
const folderTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [3200, 6160],
  rows: [
    new TableRow({ children: [
      headerCell('Pasta / Arquivo', 3200),
      headerCell('Conteúdo', 6160),
    ]}),
    ...[
      ['index.php', 'Front Controller. Ponto de entrada único da aplicação. Registra todas as rotas.'],
      ['.htaccess', 'Redireciona todas as requisições para index.php (mod_rewrite do Apache).'],
      ['config/', 'Arquivos de configuração: banco de dados (database.php) e e-mail (mail.php).'],
      ['app/Core/', 'Núcleo do MVC: Router.php (roteamento), Database.php (PDO singleton).'],
      ['app/Controllers/', 'Um controller por módulo. Recebem a requisição, chamam o model e renderizam a view.'],
      ['app/Models/', 'Um model por entidade. Encapsulam toda a lógica de acesso ao banco de dados.'],
      ['app/Middleware/', 'AuthMiddleware (verifica login) e CsrfMiddleware (valida token CSRF em POST).'],
      ['app/Helpers/', 'Validator.php (validação de dados), SessionHelper.php, Csrf.php.'],
      ['app/Services/', 'MailService.php — abstração do PHPMailer para envio de e-mails.'],
      ['views/', 'Arquivos PHP de apresentação. Organizados por módulo: auth/, dashboard/, ocorrencias/, etc.'],
      ['views/layouts/', 'header.php e footer.php — usados por todas as views como moldura visual.'],
      ['database/', 'schema.sql — script único para criação completa do banco com dados iniciais.'],
      ['public/', 'Pasta raiz do servidor web. Contém apenas app.js e assets públicos.'],
      ['storage/', 'Logs da aplicação e arquivos de sessão. Não vai ao repositório.'],
    ].map(([pasta, desc]) => new TableRow({ children: [
      cellText(pasta, { bold: true, width: 3200, bg: COR_CINZA_CLR }),
      cellText(desc, { width: 6160 }),
    ]}))
  ]
});

// ── Tabela de banco de dados ──────────────────────────────
const dbTable = () => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [2600, 6760],
  rows: [
    new TableRow({ children: [
      headerCell('Tabela', 2600),
      headerCell('O que armazena', 6760),
    ]}),
    ...[
      ['usuarios', 'Todos os usuários do sistema: gestores, professores e técnicos. Campos: id, nome, email, senha (hash bcrypt), tipo, ativo, data_cadastro.'],
      ['login_attempts', 'Tentativas de login por e-mail. Usada para o bloqueio após 5 falhas em 15 minutos.'],
      ['password_resets', 'Tokens de recuperação de senha com prazo de validade de 60 minutos.'],
      ['laboratorio', 'Laboratórios cadastrados. Campos: id, nome, localização, descrição, capacidade, ativo.'],
      ['equipamento', 'Equipamentos vinculados a laboratórios. Campos: id, id_laboratorio (FK), nome, número de série, ativo.'],
      ['tipo_problema', 'Categorias de problemas. Campos: id, descricao (única), ativo.'],
      ['ocorrencia', 'Tabela central. Armazena cada chamado com FK para professor, técnico, laboratório, equipamento, tipo e o status atual.'],
      ['ocorrencia_historico', 'Cada mudança de status gera um registro: status anterior, status novo, usuário responsável, observação e timestamp.'],
    ].map(([tab, desc]) => new TableRow({ children: [
      cellText(tab, { bold: true, width: 2600, bg: COR_CINZA_CLR }),
      cellText(desc, { width: 6760 }),
    ]}))
  ]
});

// ══════════════════════════════════════════════════════════
// DOCUMENTO PRINCIPAL
// ══════════════════════════════════════════════════════════
const doc = new Document({
  styles: {
    default: {
      document: { run: { font: 'Arial', size: 22, color: COR_CINZA_ESC } }
    },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: 'Arial', size: 32, bold: true, color: COR_AZUL },
        paragraph: { spacing: { before: 400, after: 200 }, outlineLevel: 0 } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: 'Arial', size: 26, bold: true, color: COR_AZUL_CLARO },
        paragraph: { spacing: { before: 300, after: 160 }, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC },
        paragraph: { spacing: { before: 220, after: 120 }, outlineLevel: 2 } },
    ]
  },
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{
          level: 0, format: LevelFormat.BULLET, text: '\u2022',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } }
        }]
      },
      {
        reference: 'numbered',
        levels: [{
          level: 0, format: LevelFormat.DECIMAL, text: '%1.',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } }
        }]
      }
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1260, bottom: 1440, left: 1260 }
      }
    },
    children: [

      // ── CAPA ────────────────────────────────────────────
      spacer(800),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 80 },
        children: [new TextRun({
          text: 'UNIEINSTEIN – Centro Universitário Einstein Limeira',
          font: 'Arial', size: 22, color: COR_CINZA_MED
        })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 80 },
        children: [new TextRun({
          text: 'Tecnologia em Análise e Desenvolvimento de Sistemas — TADS · 3º Semestre · 2026',
          font: 'Arial', size: 20, color: COR_CINZA_MED
        })]
      }),
      spacer(400),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: {
          top: { style: BorderStyle.SINGLE, size: 12, color: COR_AZUL, space: 4 },
          bottom: { style: BorderStyle.SINGLE, size: 12, color: COR_AZUL, space: 4 },
        },
        spacing: { before: 200, after: 200 },
        children: [new TextRun({
          text: 'Lab Relator',
          font: 'Arial', size: 64, bold: true, color: COR_AZUL
        })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 80, after: 400 },
        children: [new TextRun({
          text: 'Sistema Relator de Problemas em Laboratório',
          font: 'Arial', size: 28, color: COR_AZUL_CLARO, bold: true
        })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 120 },
        children: [new TextRun({ text: 'Documentação Pública do Projeto', font: 'Arial', size: 24, color: COR_CINZA_MED })]
      }),
      spacer(600),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 60 },
        children: [new TextRun({ text: 'Autores', font: 'Arial', size: 20, bold: true, color: COR_CINZA_ESC })]
      }),
      ...['Adriel Venancio Buccier', 'Carlos Victor Pinto Fiuza', 'Juan David Moreno'].map(a =>
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 40 },
          children: [new TextRun({ text: a, font: 'Arial', size: 22, color: COR_CINZA_ESC })]
        })
      ),
      spacer(400),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 0 },
        children: [new TextRun({ text: 'Limeira — 2026', font: 'Arial', size: 20, color: COR_CINZA_MED })]
      }),
      pageBreak(),

      // ── 1. O QUE É O SISTEMA ────────────────────────────
      h1('1. O Que É o Lab Relator'),
      p('O Lab Relator é um sistema web desenvolvido para gerenciar ocorrências em laboratórios de informática de instituições de ensino. Ele permite que professores registrem problemas encontrados durante suas aulas — como equipamentos com defeito, falhas elétricas ou problemas de conectividade — e que técnicos especializados acompanhem e resolvam esses chamados de forma organizada.', { justify: true }),
      p('O objetivo central é eliminar o registro informal de problemas (bilhetes, mensagens avulsas, ligações) e centralizar toda a comunicação entre professores, técnicos e gestores em um único lugar, com rastreabilidade completa de cada ocorrência desde a abertura até o encerramento.', { justify: true }),
      spacer(80),

      colorCard(
        'Problema que o sistema resolve',
        [
          'Sem o Lab Relator: professor detecta problema no laboratório, avisa verbalmente ou por mensagem, técnico não tem registro formal, gestor não sabe o volume real de problemas, não há histórico de resolução.',
          'Com o Lab Relator: professor abre chamado no sistema, técnico recebe no monitor de chamados, inicia e encerra com registro, gestor acompanha tudo em tempo real com relatórios e exportação de dados.',
        ],
        COR_AZUL_BG, '1e3a5f', COR_AZUL_CLARO
      ),
      spacer(200),

      // ── 2. PERFIS DE USUÁRIO ─────────────────────────────
      h1('2. Perfis de Usuário'),
      p('O sistema trabalha com três perfis distintos, cada um com acesso restrito às funcionalidades pertinentes à sua função:'),
      spacer(80),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [3120, 3120, 3120],
        rows: [
          new TableRow({ children: [
            headerCell('Gestor', 3120),
            headerCell('Professor', 3120, COR_VERDE),
            headerCell('Técnico', 3120, '92400E'),
          ]}),
          new TableRow({ children: [
            cell([
              new Paragraph({ spacing: { before: 40, after: 40 }, children: [new TextRun({ text: 'Administrador do sistema. Gerencia todos os cadastros, acompanha todas as ocorrências, acessa relatórios completos e pode reabrir chamados encerrados.', font: 'Arial', size: 20, color: COR_CINZA_ESC })] })
            ], { bg: COR_AZUL_BG, width: 3120 }),
            cell([
              new Paragraph({ spacing: { before: 40, after: 40 }, children: [new TextRun({ text: 'Usuário que utiliza os laboratórios. Pode registrar ocorrências e acompanhar somente os chamados que ele mesmo abriu.', font: 'Arial', size: 20, color: COR_CINZA_ESC })] })
            ], { bg: COR_VERDE_BG, width: 3120 }),
            cell([
              new Paragraph({ spacing: { before: 40, after: 40 }, children: [new TextRun({ text: 'Responsável técnico pela resolução. Acessa o monitor de chamados, inicia o atendimento e registra o encerramento com observações.', font: 'Arial', size: 20, color: COR_CINZA_ESC })] })
            ], { bg: COR_LARANJA_BG, width: 3120 }),
          ]}),
        ]
      }),
      spacer(240),

      h2('2.1 Credenciais Padrão (Ambiente de Desenvolvimento)'),
      p('O banco de dados já vem populado com três usuários para testes imediatos. Estas credenciais devem ser alteradas antes de qualquer uso em produção.'),
      spacer(80),
      credTable(),
      spacer(200),

      // ── 3. MÓDULOS DO SISTEMA ─────────────────────────────
      pageBreak(),
      h1('3. Módulos do Sistema'),
      p('O Lab Relator é composto por dez módulos funcionais. A tabela abaixo descreve cada um, quem tem acesso e o que ele realiza:'),
      spacer(80),
      modulosTable(),
      spacer(200),

      // ── 4. CICLO DE VIDA DA OCORRÊNCIA ──────────────────
      h1('4. Ciclo de Vida de uma Ocorrência'),
      p('Cada chamado aberto no sistema passa por uma sequência controlada de estados. As transições entre estados são validadas no backend — nenhuma mudança inválida é aceita pelo sistema.', { justify: true }),
      spacer(80),

      h2('4.1 Estados Possíveis'),
      statusTable(),
      spacer(200),

      h2('4.2 Fluxo Completo'),
      spacer(80),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [480, 2200, 6680],
        rows: [
          new TableRow({ children: [
            headerCell('#', 480),
            headerCell('Quem age', 2200),
            headerCell('O que acontece', 6680),
          ]}),
          ...[
            ['1', 'Professor', 'Acessa "Nova Ocorrência", seleciona o laboratório, equipamento (opcional) e tipo do problema, descreve a situação e envia. O status inicial é definido automaticamente como Não Atendida. O sistema envia e-mail de notificação ao gestor.'],
            ['2', 'Técnico ou Gestor', 'Acessa o Monitor de Chamados e visualiza todos os chamados com status Não Atendida. Clica em "Iniciar Atendimento", o status muda para Em Atendimento e o técnico é vinculado ao chamado.'],
            ['3', 'Professor (opcional)', 'Enquanto o chamado está Não Atendida, o professor pode editar a descrição ou mover para Em Edição para revisar as informações antes do atendimento começar.'],
            ['4', 'Técnico ou Gestor', 'Após resolver o problema, registra uma observação e encerra o chamado. O status muda para Encerrada e a data de encerramento é gravada. O sistema envia e-mail ao professor notificando a resolução.'],
            ['5', 'Gestor (se necessário)', 'Se o problema voltar a ocorrer, o gestor pode reabrir o chamado encerrado, voltando-o para Não Atendida para um novo ciclo de atendimento.'],
          ].map(([n, quem, oque]) => new TableRow({ children: [
            cellText(n, { bold: true, center: true, bg: COR_CINZA_CLR, width: 480 }),
            cellText(quem, { bold: true, width: 2200, bg: COR_CINZA_CLR }),
            cellText(oque, { width: 6680 }),
          ]}))
        ]
      }),
      spacer(200),

      h2('4.3 Regras de Negócio Principais'),
      bullet('O id do professor é sempre capturado da sessão autenticada — nunca do formulário enviado pelo navegador.'),
      bullet('Professor só pode editar ou cancelar uma ocorrência que ainda está com status Não Atendida.'),
      bullet('Professor só enxerga as próprias ocorrências na listagem.'),
      bullet('Técnico e gestor visualizam todos os chamados no monitor.'),
      bullet('Apenas o gestor pode reabrir um chamado já encerrado.'),
      bullet('Todo logout é realizado via POST com validação CSRF — impossível forçar logout por link.'),
      bullet('Após 5 tentativas de login falhas em 15 minutos, o acesso fica bloqueado temporariamente.'),
      spacer(200),

      // ── 5. ARQUITETURA TÉCNICA ───────────────────────────
      pageBreak(),
      h1('5. Arquitetura Técnica'),
      p('O Lab Relator foi desenvolvido com arquitetura MVC (Model-View-Controller) própria, sem uso de frameworks como Laravel ou Symfony. Esta decisão foi intencional: o objetivo acadêmico do projeto é demonstrar o entendimento dos conceitos fundamentais do padrão arquitetural.', { justify: true }),
      spacer(80),

      h2('5.1 Padrão MVC'),
      spacer(80),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [1800, 2400, 5160],
        rows: [
          new TableRow({ children: [
            headerCell('Camada', 1800),
            headerCell('Responsabilidade', 2400),
            headerCell('Exemplo no projeto', 5160),
          ]}),
          ...[
            ['Model', 'Acesso ao banco de dados e regras de negócio.', 'OcorrenciaModel.php: métodos create(), changeStatus(), paginate(), getHistorico().'],
            ['View', 'Apresentação dos dados ao usuário.', 'views/ocorrencias/list.php: exibe a listagem de chamados com badges coloridos por status.'],
            ['Controller', 'Recebe a requisição, aciona o Model e escolhe a View.', 'OcorrenciaController.php: método registrar() valida os dados, chama o model e redireciona.'],
          ].map(([c, r, e]) => new TableRow({ children: [
            cellText(c, { bold: true, bg: COR_AZUL_BG, color: '1e3a5f', width: 1800 }),
            cellText(r, { width: 2400 }),
            cellText(e, { width: 5160 }),
          ]}))
        ]
      }),
      spacer(200),

      h2('5.2 Fluxo de uma Requisição'),
      p('O caminho percorrido desde o clique do usuário até a resposta na tela segue sempre a mesma sequência:'),
      spacer(80),

      colorCard(
        'Exemplo: Professor clica em "Salvar" ao registrar uma ocorrência',
        [
          '1.  Navegador envia POST /ocorrencia/registrar com os dados do formulário.',
          '2.  Apache recebe e redireciona para Lab_relator/public/index.php (.htaccess).',
          '3.  index.php inicializa a sessão, carrega o Composer e registra as rotas.',
          '4.  Router identifica a rota POST /ocorrencia/registrar e executa os middlewares:',
          '      a) AuthMiddleware verifica se o usuário está autenticado.',
          '      b) CsrfMiddleware valida o token CSRF do formulário.',
          '      c) Role middleware confirma que o usuário é professor.',
          '5.  OcorrenciaController::registrar() é executado:',
          '      a) Validator valida os campos (laboratório, tipo, descrição).',
          '      b) id_professor é capturado de $_SESSION[\'usuario_id\'].',
          '      c) OcorrenciaModel::create() executa o INSERT via PDO prepared statement.',
          '      d) MailService::enviarNovaOcorrencia() notifica o gestor por e-mail.',
          '6.  Controller redireciona para /ocorrencia com flash de sucesso.',
          '7.  Usuário vê a listagem atualizada com o novo chamado.',
        ],
        COR_CINZA_CLR, COR_AZUL, COR_CINZA_MED
      ),
      spacer(200),

      h2('5.3 Estrutura de Pastas'),
      spacer(80),
      folderTable(),
      spacer(200),

      // ── 6. BANCO DE DADOS ────────────────────────────────
      pageBreak(),
      h1('6. Banco de Dados'),
      p('O sistema utiliza MySQL 8.x com ENGINE InnoDB, charset utf8mb4 (suporte completo a Unicode e emojis) e todas as relações protegidas por chaves estrangeiras com políticas de integridade referencial.', { justify: true }),
      spacer(80),

      h2('6.1 Tabelas do Sistema'),
      spacer(80),
      dbTable(),
      spacer(200),

      h2('6.2 Decisões de Modelagem'),
      bullet('Senhas:', 'armazenadas exclusivamente como hash bcrypt (cost 12) via password_hash() do PHP. Nunca em texto puro.'),
      bullet('Soft delete:', 'todas as tabelas principais têm um campo "ativo" (0/1). Nenhum dado é excluído fisicamente — apenas desativado.'),
      bullet('Histórico de status:', 'cada transição de status gera um registro em ocorrencia_historico, garantindo auditoria completa de quem alterou o quê e quando.'),
      bullet('Prepared statements:', 'todas as queries usam PDO com parâmetros vinculados — proteção completa contra SQL Injection.'),
      bullet('Status como ENUM:', 'o campo status na tabela ocorrencia usa ENUM do MySQL, garantindo integridade de domínio no nível do banco de dados.'),
      spacer(200),

      // ── 7. TECNOLOGIAS ───────────────────────────────────
      h1('7. Tecnologias Utilizadas'),
      spacer(80),
      techTable(),
      spacer(200),

      // ── 8. SEGURANÇA ─────────────────────────────────────
      h1('8. Segurança Implementada'),
      p('O projeto aplica as principais defesas recomendadas para aplicações web, cobrindo as vulnerabilidades mais comuns listadas pelo OWASP:'),
      spacer(80),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2800, 6560],
        rows: [
          new TableRow({ children: [
            headerCell('Ameaça', 2800),
            headerCell('Como o Lab Relator se protege', 6560),
          ]}),
          ...[
            ['SQL Injection', 'Uso exclusivo de PDO com prepared statements. Nenhuma variável de usuário é concatenada diretamente em queries SQL.'],
            ['XSS (Cross-Site Scripting)', 'Todo valor exibido em HTML passa por htmlspecialchars($v, ENT_QUOTES, \'UTF-8\') antes de ser renderizado.'],
            ['CSRF (Cross-Site Request Forgery)', 'Todo formulário POST inclui um token CSRF único por sessão, validado pelo CsrfMiddleware antes de qualquer ação.'],
            ['Força bruta no login', 'Bloqueio automático após 5 tentativas falhas em 15 minutos, registradas na tabela login_attempts.'],
            ['Senhas expostas', 'Senhas armazenadas como hash bcrypt com cost 12. Recuperação gera token temporário (60 min), nunca envia a senha.'],
            ['Acesso não autorizado', 'AuthMiddleware bloqueia qualquer rota protegida sem sessão ativa. Role middleware verifica o perfil para cada rota específica.'],
            ['Hijacking de sessão', 'session_regenerate_id(true) é executado após login bem-sucedido. Cookies com HttpOnly e SameSite=Strict.'],
            ['Manipulação de IDs', 'id_professor e id_tecnico são sempre lidos da sessão do servidor, nunca do corpo da requisição POST.'],
          ].map(([a, p]) => new TableRow({ children: [
            cellText(a, { bold: true, bg: COR_CINZA_CLR, width: 2800 }),
            cellText(p, { width: 6560 }),
          ]}))
        ]
      }),
      spacer(200),

      // ── 9. COMO RODAR ────────────────────────────────────
      pageBreak(),
      h1('9. Como Executar o Projeto'),
      spacer(80),

      colorCard(
        'Pré-requisitos',
        [
          'XAMPP 8.2+  (Apache + MySQL)  →  https://www.apachefriends.org',
          'Composer 2.x                  →  https://getcomposer.org',
          'Git 2.x                       →  https://git-scm.com',
        ],
        COR_AMARELO_BG, COR_AMARELO, 'D97706'
      ),
      spacer(200),

      h2('Passos de Instalação'),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Clone o repositório: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'git clone https://github.com/JDavidSMoreno/Projeto-integrador-1-TADS.git', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Instale as dependências PHP: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'cd Lab_relator && composer install', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Importe o banco de dados: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'Crie o banco "lab_relator" no phpMyAdmin e importe database/schema.sql.', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Configure o banco: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'Edite config/database.php com host, usuário e senha do seu MySQL local.', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Configure o Virtual Host: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'Aponte o Apache para Lab_relator/public/ como DocumentRoot.', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      new Paragraph({
        spacing: { before: 60, after: 60 },
        numbering: { reference: 'numbered', level: 0 },
        children: [new TextRun({ text: 'Acesse o sistema: ', font: 'Arial', size: 22, bold: true, color: COR_CINZA_ESC }),
                   new TextRun({ text: 'http://labrelator.local no navegador.', font: 'Arial', size: 22, color: COR_CINZA_ESC })]
      }),
      spacer(120),
      p('Para o guia completo com capturas de tela, configuração de Virtual Host passo a passo e resolução de problemas comuns, consulte o arquivo Documentacao/Como rodar localmente.pdf no repositório.', { italic: true }),
      spacer(200),

      // ── 10. REPOSITÓRIO ──────────────────────────────────
      h1('10. Repositório e Documentação'),
      spacer(80),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2400, 6960],
        rows: [
          new TableRow({ children: [
            headerCell('Recurso', 2400),
            headerCell('Localização', 6960),
          ]}),
          ...[
            ['Código-fonte', 'https://github.com/JDavidSMoreno/Projeto-integrador-1-TADS'],
            ['Schema SQL', 'Lab_relator/database/schema.sql'],
            ['Guia de instalação', 'Documentacao/Como rodar localmente.pdf'],
            ['Documentação técnica', 'Documentacao/Documentacao_Tecnica_Fases_1_2.md'],
            ['Documento do projeto', 'Documentacao/Projeto_Integrador_TADS.docx'],
            ['Cronograma', 'Documentacao/ModeloCronograma.xlsx'],
          ].map(([rec, loc]) => new TableRow({ children: [
            cellText(rec, { bold: true, bg: COR_CINZA_CLR, width: 2400 }),
            cellText(loc, { width: 6960 }),
          ]}))
        ]
      }),
      spacer(400),

      // ── RODAPÉ ───────────────────────────────────────────
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: { top: { style: BorderStyle.SINGLE, size: 4, color: 'CCCCCC', space: 4 } },
        spacing: { before: 200, after: 60 },
        children: [new TextRun({ text: 'Lab Relator · Projeto Integrador TADS · UniEinstein Limeira · 2026', font: 'Arial', size: 18, color: COR_CINZA_MED })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 0 },
        children: [new TextRun({ text: 'Adriel Venancio Buccier · Carlos Victor Pinto Fiuza · Juan David Moreno', font: 'Arial', size: 18, color: COR_CINZA_MED })]
      }),

    ]
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUTPUT_FILE, buf);
  console.log(`Documento gerado com sucesso: ${OUTPUT_FILE}`);
}).catch(e => { console.error(e); process.exit(1); });
