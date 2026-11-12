# 0018 — A listagem não faz uma consulta por chamado

**Marco:** M4

## Contexto

Cada chamado da listagem traz o SLA, e o SLA sai dos eventos. Buscando os eventos chamado a chamado, uma página de 20 viraria 20 consultas a mais (o problema N+1).

## Decisão

- A listagem carrega solicitante, atendente e eventos junto (*eager loading*).
- `Chamado::marcosDoSla()` monta a linha do tempo a partir da relação já carregada, sem consultar.
- O SLA só aparece quando os eventos foram carregados (`whenLoaded`): ninguém dispara a consulta sem querer.

## Alternativas descartadas

- **Buscar os eventos dentro do cálculo:** funcionaria, com uma consulta por chamado.

## Consequências

- A listagem traz todos os eventos dos 20 chamados da página. Para chamados com centenas de eventos, valeria carregar só as colunas que o cálculo usa.

## Como é verificado

- `SlaTest::test_a_listagem_nao_faz_uma_consulta_por_chamado`, que conta as consultas com 5 chamados
- `SlaTest::test_critico_parado_na_fila_aparece_estourado_na_listagem`
