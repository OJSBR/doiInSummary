# DOI in Summary — OJS, OPS and OMP plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![OPS](https://img.shields.io/badge/OPS-3.5-brightgreen)](https://pkp.sfu.ca/software/ops/)
[![OMP](https://img.shields.io/badge/OMP-3.5-brightgreen)](https://pkp.sfu.ca/software/omp/)
[![Version](https://img.shields.io/badge/version-3.5.1.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS, OPS and OMP 3.5](https://github.com/OJSBR/doiInSummary/releases/download/3.5.1.0/doiInSummary-3.5.1.0.tar.gz) · [OJS 3.4](https://github.com/OJSBR/doiInSummary/releases/download/3.4.0.4/doiInSummary-3.4.0.4.tar.gz) — or browse all [Releases](../../releases).

A generic plugin that shows the **DOI URL** of each item in the lists readers browse, with the
same package for the three PKP applications:

- **OJS** — each article in the issue table of contents, in the current issue on the journal
  home page and in search results, right under the title;
- **OPS** — each preprint in the lists of preprints (server home page, section pages, search
  results), right after the authors;
- **OMP** — each book in the catalogue, on the press home page (featured books, new releases),
  in series and categories and in search results, right after the authors.

> **OPS users:** this package replaces `doiInSummaryOps`, which is no longer maintained.

> **Maintained by [OJSBR](https://ojsbr.com).** Adapted for OJS, OPS and OMP 3.5 from the original
> `doiInSummary` plugin by **Lepidus Tecnologia**. See the
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| Application | Branch | Plugin release |
|-------------|--------|----------------|
| OJS, OPS and OMP 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 3.5.1.0 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.4 |

The 3.4 branch is for OJS only. The locale folders follow each PKP line (38 languages).

## Installation

1. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
   into `plugins/generic/` so you get `plugins/generic/doiInSummary/`. Do not rename the folder.
2. Enable **DOI in summary** under the *Generic* plugins list.

## How it works (technical)

- **OJS and OPS:** the hook that ends each summary (`Templates::Issue::Issue::Article` in OJS,
  `Templates::Archive::Preprint` in OPS) appends the DOI; the handler accepts both
  `TemplateManager` and `Smarty_Internal_Template`, which 3.5 themes pass. One script for the
  page (`js/doiInSummary.js`, `addJavaScript`) moves every DOI under the title of its article, or
  after the authors of its preprint; without JavaScript the DOI stays at the end of the summary.
  No inline script per item. (OPS 3.5 still looks for the DOI in `$pubIdPlugins`, which has not
  held the DOI since DOIs moved to the core in 3.4, so the core never shows it in the lists.)
- **OMP:** the book summary (`monograph_summary.tpl`) has no hook, so the plugin uses a named
  Smarty output filter, registered from `TemplateManager::display` only on reader pages that list
  books with a DOI. The books are taken from what the page handler gave the template, before the
  page is rendered, so no query is added; the DOI is inserted after the authors of each summary
  (after the title when the theme shows no authors), recognised by its `catalog/book/<id>` link.
- In every application the DOI is read from the current publication (`doiObject`, then the
  stored pub-id) and shown as its resolving URL (`https://doi.org/...`), whatever form it was
  stored in. The label is the application's own (`doi.readerDisplayName`).

## Tests

- **PHPUnit** (`tests/*Test.php`, on `PKP\tests\PKPTestCase`): the class against the installed PKP,
  the plugin found by PKP's plugin registry, the hook of the running application present in its
  summary template (and no hook in the OMP one), the resolving URL of every DOI form, the DOI read
  from the current publication, the summary markup without inline script, the assets and the OMP
  output filter added to reader pages only, the books found in the template variables (lists,
  search results), the DOI inserted once in each book summary after the authors or the title and
  nowhere else, and the 38 translations. From the root of the application:

  ```bash
  lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage "$PWD/plugins/generic/doiInSummary/tests"
  ```

- **Cypress** (`cypress/tests/functional/DoiInSummary.cy.js`, run by
  [pkp-github-actions](https://github.com/pkp/pkp-github-actions) on every push, on OJS, OPS and
  OMP): gives the published submissions a DOI through the REST API, checks that the list shows no
  DOI while the plugin is off, enables it, and checks that every summary shows its DOI once, as
  the resolving link of the DOI the API reports, with one colon after the label, in its place.
- **On OMP 3.5.0.5** (theme Amplitude): catalogue (25 books), home page (15) and search results,
  each DOI compared with the database; none with the plugin off. **On OJS 3.5.0.5:** the issue
  tables of contents show the same DOIs as with 3.5.0.6.

Tests are kept in the repository and are not part of the release package.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com) — adaptation to OJS 3.4/3.5, OPS and OMP 3.5.
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

Plugin genérico que exibe a **URL do DOI** de cada item nas listas que o leitor percorre, com o
mesmo pacote para as três aplicações da PKP:

- **OJS** — cada artigo no sumário da edição, na edição atual da página inicial da revista e nos
  resultados da busca, logo abaixo do título;
- **OPS** — cada preprint nas listas de preprints (página inicial do servidor, páginas das seções,
  resultados da busca), logo depois dos autores;
- **OMP** — cada livro no catálogo, na página inicial da editora (destaques, lançamentos), nas
  séries e categorias e nos resultados da busca, logo depois dos autores.

> **Quem usa OPS:** este pacote substitui o `doiInSummaryOps`, que não é mais mantido.

**⬇️ Pacote de instalação:** [OJS, OPS e OMP 3.5](https://github.com/OJSBR/doiInSummary/releases/download/3.5.1.0/doiInSummary-3.5.1.0.tar.gz) · [OJS 3.4](https://github.com/OJSBR/doiInSummary/releases/download/3.4.0.4/doiInSummary-3.4.0.4.tar.gz) — ou veja todas as [Releases](../../releases).

> **Mantido pela [OJSBR](https://ojsbr.com).** Adaptado para OJS, OPS e OMP 3.5 a partir do plugin
> original `doiInSummary` da **Lepidus Tecnologia**. Veja a seção
> [Créditos e autoria](#créditos-e-autoria) abaixo.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/doiInSummary/`); não renomeie a pasta.
Depois ative o **DOI in summary** na lista de plugins *Genéricos*.

### Compatibilidade e branches

| Aplicação | Branch | Release do plugin |
|-----------|--------|-------------------|
| OJS, OPS e OMP 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(padrão)* | 3.5.1.0 |
| OJS 3.4.x     | [`stable-3_4_0`](../../tree/stable-3_4_0) | 3.4.0.4 |

A branch 3.4 é só para OJS.

### Testes

PHPUnit em `tests/` (sobre `PKP\tests\PKPTestCase`) e Cypress em `cypress/tests/functional/`
(rodado pelo [pkp-github-actions](https://github.com/pkp/pkp-github-actions) a cada push), com os
comandos da seção em inglês. A suíte cobre a classe contra o PKP instalado, o plugin encontrado pelo
registro de plugins, o hook da aplicação em execução no template do resumo (e nenhum no do OMP), a
URL de resolução de cada forma de DOI, o DOI lido da publicação atual, o resumo sem script inline,
os arquivos e o filtro do OMP só nas páginas do leitor, os livros achados nas variáveis do template
e o DOI inserido uma vez em cada resumo de livro, e as 38 traduções. O Cypress, no OJS, no OPS e no
OMP, dá DOI às submissões publicadas pela API, confere que a lista não mostra DOI com o plugin
desligado, liga o plugin e confere cada DOI uma vez, igual ao da API, no lugar certo. **No OMP
3.5.0.5** (tema Amplitude): catálogo (25 livros), página inicial (15) e busca, cada DOI comparado
com o banco, e nenhum com o plugin desligado. **No OJS 3.5.0.5:** os sumários mostram os mesmos DOIs
que com a 3.5.0.6.

Os testes ficam no repositório e não fazem parte do pacote da release.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com) — adaptação para OJS 3.4/3.5, OPS e OMP 3.5.
- **Trabalho original:** `doiInSummary` da **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Uso de IA

Foi usada IA generativa (Claude, da Anthropic) para escrever e rodar testes, melhorar o código e
alinhá-lo aos padrões da PKP. Toda mudança é revisada e testada pela OJSBR, que responde pelas
releases publicadas.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
