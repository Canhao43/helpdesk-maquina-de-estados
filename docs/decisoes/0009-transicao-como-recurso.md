# 0009 — Cada transição é um recurso: POST /chamados/{id}/acoes/{acao}

**Marco:** M2

## Contexto

A API precisa expor as mudanças de estado. Chegar em "em atendimento" pode ser assumir, responder ou reabrir, e cada uma tem quem pode e efeito próprio: assumir grava o atendente, devolver à fila o apaga.

## Decisão

- Uma rota por ação, com o nome da ação na URL. `{acao}` é o enum `Acao`: valor que não existe vira `404` já na rota.
- Cada chamado devolve `acoes_disponiveis`: o que quem pergunta pode fazer agora.

## Alternativas descartadas

- **`PATCH` no campo `estado`:** o cliente escolheria o destino, e a API teria de adivinhar qual ação ele quis dizer.
- **O cliente deduzir os botões pelo estado e pelo papel:** seria reimplementar a máquina de estados no front.

## Consequências

- Uma ação nova é uma linha no enum e uma regra na policy, sem rota nova.

## Como é verificado

- `MaquinaDeEstadosTest::test_acao_que_nao_existe_responde_404`
- `MaquinaDeEstadosTest::test_cada_um_ve_as_proprias_acoes_disponiveis`
- `MaquinaDeEstadosTest::test_devolver_a_fila_solta_o_atendente`
