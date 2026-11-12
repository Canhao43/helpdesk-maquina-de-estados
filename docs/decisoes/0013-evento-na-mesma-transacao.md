# 0013 — O evento é gravado na mesma transação da mudança de estado

**Marco:** M3

## Contexto

A transição e o evento são duas escritas. Se só a primeira acontecer (disco cheio, erro de código), sobra um chamado resolvido sem registro de quem resolveu.

## Decisão

- A gravação condicional e o evento rodam dentro de uma transação, na `MaquinaDeEstados`: gravam os dois, ou nenhum.
- A abertura do chamado também passa pela máquina e grava o primeiro evento.

## Alternativas descartadas

- **Gravar o evento depois, fora da transação:** abre a janela do chamado que mudou sem rastro.
- **Evento por *listener* do model:** roda fora do controle de quem chama e esconde, na leitura do código, que toda transição tem evento.

## Consequências

- Quem perde a corrida não deixa evento: a exceção sai antes do *insert*, e o *rollback* desfaria de qualquer jeito.

## Como é verificado

- `HistoricoTest::test_se_o_evento_nao_grava_a_transicao_tambem_nao_vale`
- `HistoricoTest::test_quem_perde_a_corrida_nao_deixa_rastro_no_historico`
- `HistoricoTest::test_cada_passo_do_chamado_fica_no_historico_em_ordem`
