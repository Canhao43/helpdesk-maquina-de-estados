# 0010 — A ordem das respostas: 404, 422, 403, 422 de comentário e 409

**Marco:** M2 e M3

## Contexto

Uma ação pode falhar por vários motivos ao mesmo tempo. A ordem em que eles são checados decide o que o cliente fica sabendo.

## Decisão

1. **`404`** se o usuário não vê o chamado. Vem antes de tudo.
2. **`422`** se a ação não vale no estado atual, com as `acoes_permitidas` para esse usuário.
3. **`403`** se a ação vale no estado, mas não é dele.
4. **`422` de validação** se falta o comentário que a ação exige.
5. **`409`** se o chamado mudou entre a leitura e a gravação.

Antes de tudo isso vem o escopo do token ([ADR 0015](0015-escopo-do-token-e-teto.md)).

## Alternativas descartadas

- **Permissão antes do estado:** com o chamado aberto, ninguém é responsável por ele. O atendente que tenta resolver levaria um `403` por um motivo que não é o real; com o estado antes, ele recebe "assuma primeiro".
- **Validar o comentário primeiro, como uma validação comum:** um erro de comentário responderia antes do `404`, e confirmaria que o chamado existe. E pedir comentário a quem não pode agir é inútil.

## Consequências

- O comentário não passa por `FormRequest`: vai cru para a `MaquinaDeEstados`, que valida no passo 4.

## Como é verificado

- `MaquinaDeEstadosTest::test_quem_nao_ve_o_chamado_recebe_404_antes_de_qualquer_regra`
- `MaquinaDeEstadosTest::test_acao_que_nao_vale_no_estado_responde_422_com_o_que_vale`
- `MaquinaDeEstadosTest::test_solicitante_nao_assume_403`
- `HistoricoTest::test_sem_permissao_responde_403_antes_de_reclamar_do_comentario`
