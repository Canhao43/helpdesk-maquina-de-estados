# 0017 — O que conta no relógio do SLA

**Marco:** M4

## Contexto

Um prazo só é justo se mede o tempo que depende de quem é cobrado. Parte da vida do chamado depende do solicitante.

## Decisão

- **Horas corridas.**
- O relógio da resolução **corre** com o chamado aberto ou em atendimento. **Para** em "aguardando solicitante" e em "resolvido", porque nos dois a bola está com o solicitante. Se o chamado for reaberto, continua de onde parou. Em "fechado" ou "cancelado", congela.
- A primeira resposta é o **primeiro** `assumir`. Devolver à fila e assumir de novo não recomeça esse prazo.
- O prazo estoura só **depois** do limite: no minuto exato, ainda está dentro.
- `vence_em` só existe com o relógio correndo. Pausado, depende de quando o solicitante voltar, e dar uma data seria inventar.
- Chamado cancelado antes de alguém assumir não tem primeira resposta: não havia a quem responder.

## Alternativas descartadas

- **Horário comercial:** pede calendário de feriados, fuso e expediente. A estrutura (somar os trechos em que o relógio corre) continuaria a mesma; mudaria só a medida de cada trecho.
- **O último `assumir` como primeira resposta:** apagaria a espera real de quem abriu o chamado.

## Consequências

- Um chamado pode ficar "aguardando solicitante" para sempre sem estourar nada. Falta uma regra de fechamento automático.

## Como é verificado

- `CalculadoraDeSlaTest::test_aguardando_o_solicitante_o_relogio_para`
- `CalculadoraDeSlaTest::test_depois_da_resposta_o_relogio_volta_de_onde_parou`
- `CalculadoraDeSlaTest::test_resolvido_pausa_e_reaberto_continua_sem_contar_o_tempo_resolvido`
- `CalculadoraDeSlaTest::test_primeira_resposta_dentro_do_prazo_fica_cumprida_para_sempre`
- `CalculadoraDeSlaTest::test_fechado_congela_o_relogio`
- `CalculadoraDeSlaTest::test_cancelado_antes_de_alguem_assumir_nao_tem_primeira_resposta`
- `CalculadoraDeSlaTest::test_exatamente_no_limite_ainda_nao_estourou`
- `CalculadoraDeSlaTest::test_critica_sem_ninguem_assumir_estoura_os_dois_prazos`
