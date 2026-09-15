# DOI in Summary — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-3.5.0.6-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/doiInSummary/releases/download/3.5.0.6/doiInSummary-3.5.0.6.tar.gz) · [OJS 3.4](https://github.com/OJSBR/doiInSummary/releases/download/3.4.0.4/doiInSummary-3.4.0.4.tar.gz) — or browse all [Releases](../../releases).

> **This is the `stable-3_4_0` branch (OJS 3.4).** For OJS 3.5 use the
> [`stable-3_5_0`](../../tree/stable-3_5_0) branch.

A generic plugin for **Open Journal Systems (OJS)** that shows each article's **DOI URL** in
the issue table of contents / article summary, including the current-issue block on the
journal home page.

> **Maintained by [OJSBR](https://ojsbr.com).** Adapted for OJS 3.5 from the original
> `doiInSummary` plugin by **Lepidus Tecnologia**. See the
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 3.5.0.6 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.4 |

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

- **PHPUnit** (`tests/*Test.php`, on `PKP\tests\PKPTestCase`): the class against the installed PKP,
  the plugin found by PKP's plugin registry, the resolving URL of every DOI form, the DOI read from
  the current publication, the summary markup without inline script, the assets added to reader
  pages only, and the 38 translations. From the OJS root:

  ```bash
  lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage "$PWD/plugins/generic/doiInSummary/tests"
  ```

- **Cypress** (`cypress/tests/functional/DoiInSummary.cy.js`, run by
  [pkp-github-actions](https://github.com/pkp/pkp-github-actions) on every push): enables the
  plugin. With `withDois=1` and an issue whose articles have DOIs it also checks that each DOI
  appears once, as a resolving link, right under the title, with the script loaded once (it fails
  with the hook off).
- Verified on OJS 3.5.0.3 and 3.4.0.10.

Tests are kept in the repository and are not part of the release package.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com) — adaptation to OJS 3.4/3.5.
- **Original work:** `doiInSummary` by **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distributed under the **GNU GPL v3**, consistent with the original licensing.

## AI use

Generative AI (Claude, by Anthropic) was used to write and run tests, improve the code and bring
it in line with PKP standards. Every change is reviewed and tested by OJSBR, which is responsible
for the published releases.

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

> **Esta é a branch `stable-3_4_0` (OJS 3.4).** Para o OJS 3.5 use a branch
> [`stable-3_5_0`](../../tree/stable-3_5_0).

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/doiInSummary/`); não renomeie a pasta.
Depois ative o **DOI in summary** na lista de plugins *Genéricos*.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | [`stable-3_5_0`](../../tree/stable-3_5_0) *(padrão)* | 3.5.0.6 |
| OJS 3.4.x     | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.4 |

### Testes

PHPUnit em `tests/` (sobre `PKP\tests\PKPTestCase`) e Cypress em `cypress/tests/functional/`
(rodado pelo [pkp-github-actions](https://github.com/pkp/pkp-github-actions) a cada push), com os
comandos da seção em inglês. A suíte cobre a classe contra o PKP instalado, o plugin encontrado pelo
registro de plugins, a URL de resolução de cada forma de DOI, o DOI lido da publicação atual, o
resumo sem script inline, os arquivos só nas páginas do leitor e as 38 traduções; o Cypress, com
DOIs, confere cada DOI uma vez, como link, logo abaixo do título. Verificado no OJS 3.5.0.3 e
3.4.0.10.

Os testes ficam no repositório e não fazem parte do pacote da release.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com) — adaptação para OJS 3.4/3.5.
- **Trabalho original:** `doiInSummary` da **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Uso de IA

Foi usada IA generativa (Claude, da Anthropic) para escrever e rodar testes, melhorar o código e
alinhá-lo aos padrões da PKP. Toda mudança é revisada e testada pela OJSBR, que responde pelas
releases publicadas.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
