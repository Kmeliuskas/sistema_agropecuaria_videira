---
name: uix-feedback-use-ui-ux-pro-max
description: User wants /ui-ux-pro-max invoked for every style/UI change in this WMS project
metadata:
  type: feedback
---

Sempre invoque a skill `/ui-ux-pro-max` sempre que precisar alterar qualquer estilo, layout, tema ou UI neste projeto (WMS/Dokploy).

**Why:** O usuário pediu explicitamente ("Utilize o /ui-ux-pro-max toda vez que precisar alterar algum estilo"). Ele valoriza que as decisões de design sigam o guia da skill (tokens semânticos, ícones SVG, acessibilidade, charts) em vez de ajustes ad-hoc.

**How to apply:** Antes de editar CSS/Blade/views ou propor mudanças visuais, rode o skill (ex.: gerar design system, buscar domínios `chart`/`ux`/`style`/`color`). Combine com a regra de usar tokens semânticos (não hex hardcoded) e manter dark/light. Ver [[wms-theme-tokens]] se existir.
