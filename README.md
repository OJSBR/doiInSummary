# DOI in Summary — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-3.5.0.1-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

A generic plugin for **Open Journal Systems (OJS)** that shows each article's **DOI URL**
in the issue table of contents / article summary, including the current-issue block on
the journal home page.

> Maintained by **[OJSBR](https://ojsbr.com.br)**. Adapted for OJS 3.5 from the original
> `doiInSummary` plugin by **Lepidus Tecnologia**.

## Compatibility / branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 3.5.0.1 |

## Installation

1. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the
   folder into `plugins/generic/` so you get `plugins/generic/doiInSummary/`.
2. Enable **DOI in summary** under the *Generic* plugins list.

## Notes (OJS 3.5)

- The `Templates::Issue::Issue::Article` hook handler accepts both `TemplateManager` and
  `Smarty_Internal_Template`, which is required for OJS 3.5 theme/template rendering.
- A backward-compatible `DoiInSummaryPlugin.inc.php` loader is included for installations
  that still look up the legacy `.inc.php` filename; it simply requires the `.php` class.

## Contributing

Issues and pull requests are welcome.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE).

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que exibe a **URL do DOI** de cada
artigo no sumário da edição / resumo do artigo, incluindo o bloco da edição atual na
página inicial da revista.

> Mantido pela **[OJSBR](https://ojsbr.com.br)**. Adaptado para OJS 3.5 a partir do
> plugin original `doiInSummary` da **Lepidus Tecnologia**.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/doiInSummary/`). Depois ative o
**DOI in summary** na lista de plugins *Genéricos*.

### Notas (OJS 3.5)

- O handler do hook `Templates::Issue::Issue::Article` aceita tanto `TemplateManager`
  quanto `Smarty_Internal_Template`, necessário para a renderização de temas no OJS 3.5.
- Um loader de compatibilidade `DoiInSummaryPlugin.inc.php` está incluído para instalações
  que ainda procuram o nome legado `.inc.php`; ele apenas carrega a classe `.php`.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE).
