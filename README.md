# DOI in Summary — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-3.5.0.5-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/doiInSummary/releases/download/3.5.0.5/doiInSummary-3.5.0.5.tar.gz) · [OJS 3.4](https://github.com/OJSBR/doiInSummary/releases/download/3.4.0.3/doiInSummary-3.4.0.3.tar.gz) — or browse all [Releases](../../releases).

A generic plugin for **Open Journal Systems (OJS)** that shows each article's **DOI URL** in
the issue table of contents / article summary, including the current-issue block on the
journal home page.

> **Maintained by [OJSBR](https://ojsbr.com).** Adapted for OJS 3.5 from the original
> `doiInSummary` plugin by **Lepidus Tecnologia**. See the
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 3.5.0.5 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.3 |

Both branches ship the same code; the locale folders follow each OJS line (38 languages).

## Installation

1. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
   into `plugins/generic/` so you get `plugins/generic/doiInSummary/`. Do not rename the folder.
2. Enable **DOI in summary** under the *Generic* plugins list.

## How it works (technical)

- The `Templates::Issue::Issue::Article` hook appends the DOI to each article summary; the
  handler accepts both `TemplateManager` and `Smarty_Internal_Template`, which OJS 3.5 themes
  pass. The DOI is read from the current publication (`doiObject`, then the stored pub-id) and
  shown as its resolving URL (`https://doi.org/...`), whatever form it was stored in.
- One script for the page (`js/doiInSummary.js`, `addJavaScript`) moves every DOI right under
  its article title; without JavaScript the DOI stays at the end of the summary. No inline
  script per article.

## Tests

- **PHP suite** (`tests/`, 18 tests): the class against the installed PKP, the resolving URL
  of every DOI form, the DOI read from the current publication, the summary markup without
  inline script, and the 38 translations. Run either way from the OJS root:

  ```bash
  php plugins/generic/doiInSummary/tests/run.php
  lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage "$PWD/plugins/generic/doiInSummary/tests"
  ```

- **Cypress** (`cypress/tests/functional/DoiInSummary.cy.js`): a published issue shows each DOI
  once, as a resolving link, right under the title, with the script loaded once. No login.
- Verified on OJS 3.5.0.3 and 3.4.0.10.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com) — adaptation to OJS 3.4/3.5.
- **Original work:** `doiInSummary` by **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distributed under the **GNU GPL v3**, consistent with the original licensing.

## Contributing

Issues and pull requests are welcome.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que exibe a **URL do DOI** de cada
artigo no sumário da edição / resumo do artigo, incluindo o bloco da edição atual na página
inicial da revista.

> **Mantido pela [OJSBR](https://ojsbr.com).** Adaptado para OJS 3.5 a partir do plugin
> original `doiInSummary` da **Lepidus Tecnologia**. Veja a seção
> [Créditos e autoria](#créditos-e-autoria) abaixo.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/doiInSummary/`); não renomeie a pasta.
Depois ative o **DOI in summary** na lista de plugins *Genéricos*.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | [`stable-3_5_0`](../../tree/stable-3_5_0) *(padrão)* | 3.5.0.5 |
| OJS 3.4.x     | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.3 |

### Testes

Suíte PHP em `tests/` (18 testes, pelo `tests/run.php` ou pelo PHPUnit do PKP) e Cypress em
`cypress/tests/functional/` (sumário de uma edição publicada, sem login). Um único script por
página posiciona o DOI logo abaixo do título de cada artigo. Verificado no OJS 3.5.0.3 e 3.4.0.10.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com) — adaptação para OJS 3.4/3.5.
- **Trabalho original:** `doiInSummary` da **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
