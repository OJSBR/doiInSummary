# DOI in Summary — OJS plugin (OJS 3.4 branch)

[![OJS](https://img.shields.io/badge/OJS-3.4-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-3.4.0.1-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/doiInSummary/releases/download/3.5.0.1/doiInSummary-3.5.0.1.tar.gz) · [OJS 3.4](https://github.com/OJSBR/doiInSummary/releases/download/3.4.0.1/doiInSummary-3.4.0.1.tar.gz) — or browse all [Releases](../../releases).

> **This is the `stable-3_4_0` branch (OJS 3.4).** For OJS 3.5 use the
> [`stable-3_5_0`](../../tree/stable-3_5_0) branch.

A generic plugin for **Open Journal Systems (OJS)** that shows each article's **DOI URL** in
the issue table of contents / article summary, including the current-issue block on the
journal home page.

> **Maintained by [OJSBR](https://ojsbr.com.br).** Adapted from the original `doiInSummary`
> plugin by **Lepidus Tecnologia**. See the [Credits & authorship](#credits--authorship)
> section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 3.5.0.1 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) *(this branch)* | 3.4.0.1 |

## Installation

1. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
   into `plugins/generic/` so you get `plugins/generic/doiInSummary/`.
2. Enable **DOI in summary** under the *Generic* plugins list.

## Notes (OJS 3.4)

- The `Templates::Issue::Issue::Article` hook handler is defensive: it reads the article via
  the Smarty template object, so it works with the OJS 3.4 template rendering.
- A backward-compatible `DoiInSummaryPlugin.inc.php` loader is included for installations
  that still look up the legacy `.inc.php` filename; it simply requires the `.php` class.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com.br) — adaptation to OJS 3.4/3.5.
- **Original work:** `doiInSummary` by **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distributed under the **GNU GPL v3**, consistent with the original licensing.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version you
are working against.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

> **Esta é a branch `stable-3_4_0` (OJS 3.4).** Para OJS 3.5 use a branch
> [`stable-3_5_0`](../../tree/stable-3_5_0).

Plugin genérico para o **Open Journal Systems (OJS)** que exibe a **URL do DOI** de cada
artigo no sumário da edição / resumo do artigo, incluindo o bloco da edição atual na página
inicial da revista.

> **Mantido pela [OJSBR](https://ojsbr.com.br).** Adaptado a partir do plugin original
> `doiInSummary` da **Lepidus Tecnologia**. Veja a seção
> [Créditos e autoria](#créditos-e-autoria) abaixo.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/doiInSummary/`). Depois ative o
**DOI in summary** na lista de plugins *Genéricos*.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com.br) — adaptação para OJS 3.4/3.5.
- **Trabalho original:** `doiInSummary` da **Lepidus Tecnologia**
  (<https://github.com/lepidus/doiInSummary>), © Lepidus Tecnologia 2015–2023.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
