# 0008 — Máquina de estados escrita à mão: a tabela no enum, a permissão na policy

**Marco:** M2

## Contexto

O chamado tem 6 estados e 8 ações. Cada ação parte de certos estados e leva a um só, e cada uma tem regra própria de quem pode executar.

## Decisão

- A tabela de transições mora no enum `Acao` (`origens()` e `destino()`), num `match` que cabe numa tela.
- Quem pode cada ação mora na `ChamadoPolicy::executar`.
- Toda mudança de estado passa pela `MaquinaDeEstados`. O `estado` não está no `fillable`.
- Um teste confere a tabela inteira: estado final sem saída, nenhum beco sem saída, todo estado alcançável a partir de "aberto" e nenhuma ação que não muda nada.

## Alternativas descartadas

- **Pacote de máquina de estados** (`spatie/laravel-model-states`): uma classe por estado, configuração e uma dependência para manter, para um problema que cabe numa tabela. O custo só se paga com dezenas de estados ou com estados de comportamento próprio.

## Consequências

- Se a máquina crescer muito, a tabela num `match` deixa de caber numa tela, e vale migrar.

## Como é verificado

- `TabelaDeTransicoesTest::test_estado_final_nao_tem_saida`
- `TabelaDeTransicoesTest::test_todo_estado_que_nao_e_final_tem_saida`
- `TabelaDeTransicoesTest::test_todo_estado_e_alcancavel_a_partir_de_aberto`
- `TabelaDeTransicoesTest::test_nenhuma_acao_leva_ao_mesmo_estado_de_onde_parte`
- `MaquinaDeEstadosTest::test_caminho_completo_do_chamado`
