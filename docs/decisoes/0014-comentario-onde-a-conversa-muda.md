# 0014 — Comentário obrigatório nas ações que mudam a conversa

**Marco:** M3

## Contexto

Daqui a um mês, "pediu informação" sem dizer qual, ou "resolveu" sem dizer como, não serve para nada. Mas exigir texto em todo clique vira atrito, e o atendente passa a escrever "ok".

## Decisão

- Exigem comentário: `pedir_informacao` (o que falta), `responder` (a resposta), `resolver` (a solução), `reabrir` (por que voltou) e `devolver_a_fila` (por que soltou).
- `assumir`, `fechar` e `cancelar` se explicam sozinhas, e o comentário é opcional.
- Comentário só com espaços conta como vazio. O limite é de 5.000 caracteres.

## Alternativas descartadas

- **Comentário opcional em tudo:** o histórico fica com as transições, mas sem o motivo.
- **Obrigatório em tudo:** atrito sem ganho nas ações que não precisam de explicação.

## Consequências

- O contrato do M2 mudou no M3: essas cinco ações passaram a exigir corpo.

## Como é verificado

- `HistoricoTest::test_acao_que_pede_comentario_nao_passa_sem_ele_e_nao_muda_nada`
- `HistoricoTest::test_cada_passo_do_chamado_fica_no_historico_em_ordem`
