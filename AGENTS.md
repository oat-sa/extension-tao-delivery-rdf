# AGENTS.md — extension-tao-delivery-rdf (taoDeliveryRdf)

> Shared pillars (standards, quality / `pr-ready-gate`, Make, commit/PR):
> [nextgen-stack `tao/AGENTS.md`](https://github.com/oat-sa/nextgen-stack/blob/main/tao/AGENTS.md)
> · local: [`../AGENTS.md`](../AGENTS.md).

## 01 — Project Context

**What / why:** `oat-sa/extension-tao-delivery-rdf` (id `taoDeliveryRdf`) manages
**Deliveries via ontology**: create/publish from tests, assembly packages, group
assignment, guest access. Long work often goes through the **Task Queue**.

**Not:** test-runner UI, proctor monitor, or non-RDF `taoDelivery` alone.

**Key directories / stack / constraints:**

```text
manifest.php
controller/DeliveryMgmt.php, Publish.php, …
model/DeliveryFactory.php, DeliveryAssemblyService.php, …
install/ontology/
view/form/                 # PHP forms (view/ not views/)
views/js/                  # thin FE
scripts/
test/
```

- Stack: depends on `taoDelivery`, groups, items/tests/QTI; thin FE; usually no
  `views/package.json`.
- Versions from composer/CI.

**Docs:** [`README.md`](README.md). Shared docs / decision-log rules → parent AGENTS.

## 02 — Standards & Conventions

Package-only below. Family patterns, quality SoT, `pr-ready-gate`, polar-star →
**parent AGENTS**.

**Patterns / structure:**

- Keep FE thin; Task Queue / DeliveryCreated|Updated|Removed are contracts.
- Publishing couples to `taoQtiTest` assembly.

**Never do (this package):**

- Confuse `view/form` with `views/`; bypass Task Queue when local pattern uses it.
- Implement proctoring UI; break assembly lightly; hand-edit loaders.

**Ownership**

| Surface | Own? |
|---------|------|
| Deliveries library / wizard / assign | **Yes** |
| Publish actions | **Yes** |
| Proctor / Test runner | **No** |

## 03 — Build & Test Commands

Shared Make / CI / readiness / commit policy → **parent AGENTS**
([commit/PR policy](https://oat-sa.atlassian.net/wiki/x/_oXmqQ)).

**This package** (from Composer platform root):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist taoDeliveryRdf/test
npx grunt eslint:extensionreport --extension=taoDeliveryRdf --force
npx grunt taobundle --extension=taoDeliveryRdf
```
