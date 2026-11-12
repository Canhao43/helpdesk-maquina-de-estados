# 0002 — Quem vê o quê na listagem é filtrado na consulta

**Marco:** M1

## Contexto

A listagem é paginada. O solicitante vê só os próprios chamados; atendente e admin veem todos.

## Decisão

- Um escopo de consulta (`Chamado::visiveisPara`) aplica a regra no `WHERE`, antes da paginação.

## Alternativas descartadas

- **Buscar a página e filtrar com a policy, item a item:** uma página de 20 viria com 3 visíveis, e o `meta.total` contaria chamados que o usuário não pode ver. Isso é vazamento pela contagem.

## Consequências

- A regra de visibilidade fica em dois lugares que precisam concordar: o escopo (para listas) e a policy `view` (para um chamado). Os dois usam o mesmo critério, `papel->atende()` ou dono.

## Como é verificado

- `ChamadoTest::test_solicitante_lista_so_os_proprios`, que confere também o `meta.total`
- `ChamadoTest::test_atendente_e_admin_listam_todos`
