# 0012 — Histórico imutável no model e no banco

**Marco:** M3

## Contexto

O histórico do chamado é a prova do que aconteceu, e é dele que sai o SLA ([ADR 0016](0016-sla-calculado-do-historico.md)). Um histórico que pode ser reescrito não prova nada.

## Decisão

- O model `EventoDoChamado` recusa `update` e `delete`.
- Dois triggers no banco abortam qualquer `UPDATE` ou `DELETE` na tabela `eventos_do_chamado`, venha de onde vier.
- O evento referencia chamado e autor com `restrictOnDelete`: apagar um ou outro não some com o histórico.

## Alternativas descartadas

- **Só o model:** protege só quem passa por ele. `DB::table()->update()`, um `tinker` ou outro sistema no mesmo banco passam direto. Foi o que o projeto anterior (`integracao-erp-resiliente`) fez e deixou como pendência.
- **Usuário de banco só com `INSERT` nessa tabela:** pede um servidor de banco com usuários, que o SQLite não tem.

## Consequências

- Não dá para corrigir um evento errado: a correção é um evento novo.
- Os triggers usam a sintaxe do SQLite ([ADR 0007](0007-sqlite-com-espera.md)).

## Como é verificado

- `HistoricoTest::test_o_model_recusa_alterar_e_apagar_evento`
- `HistoricoTest::test_o_banco_recusa_alterar_e_apagar_evento_mesmo_por_fora_do_model`
- `ci.yml`: *Histórico pela API, e imutável até para o cliente do banco*, que tenta um `UPDATE` com o `sqlite3` direto no arquivo
