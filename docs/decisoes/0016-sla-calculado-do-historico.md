# 0016 — O SLA é calculado do histórico, por uma classe pura

**Marco:** M4

## Contexto

O SLA depende de quanto tempo o chamado passou em cada estado. Esse tempo já está registrado, de forma imutável, no histórico ([ADR 0012](0012-historico-imutavel-no-banco.md)).

## Decisão

- Nada de SLA fica gravado. A cada leitura, `CalculadoraDeSla` recebe a linha do tempo (os marcos do histórico) e o "agora", e devolve o resultado.
- A calculadora não toca no banco nem no relógio do sistema. O teste monta "aberto há 5 horas" sem esperar e sem mexer no relógio global.
- Os prazos ficam no enum `Prioridade`, em minutos.
- Chamado sem histórico (criado por fora da API) sai com `sla: null`, em vez de um número inventado.

## Alternativas descartadas

- **Colunas `consumido` e `vence_em` atualizadas a cada transição:** mais rápidas de consultar, mas são uma segunda verdade, que um bug numa transição deixa diferente do histórico. E uma regra nova não valeria para o passado.
- **Prazos numa tabela ou no `.env`:** fica para quando alguém precisar configurar sem deploy.

## Consequências

- O SLA é recalculado a cada leitura.
- Não dá para filtrar "estourados" no banco. Filtrar depois de buscar quebraria a paginação ([ADR 0002](0002-visibilidade-filtrada-na-consulta.md)).

## Como é verificado

- `CalculadoraDeSlaTest::test_chamado_aberto_ha_uma_hora`
- `CalculadoraDeSlaTest::test_sem_historico_nao_ha_sla`
- `SlaTest::test_o_sla_segue_o_historico_e_para_enquanto_se_espera_o_solicitante`
- `SlaTest::test_chamado_sem_historico_nao_tem_sla`
