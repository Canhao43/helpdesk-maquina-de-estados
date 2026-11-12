# 0001 — Chamado de outra pessoa responde 404, com o mesmo corpo de um inexistente

**Marco:** M1

## Contexto

O solicitante só pode ver os próprios chamados. A resposta padrão para "existe, mas não é seu" é `403`. Os ids são sequenciais, então quem testasse `1, 2, 3...` saberia quais chamados existem e quantos há.

## Decisão

- A policy nega a leitura com `Response::denyAsNotFound()`: chamado de outra pessoa responde `404`.
- Todo `404` da API sai com o mesmo corpo (`{"message": "Não encontrado."}`), por um `render` em `bootstrap/app.php`.

## Alternativas descartadas

- **`403`:** é o código "mais correto", mas confirma que o chamado existe.
- **Só o `denyAsNotFound`, sem padronizar o corpo:** o status ficaria igual, mas a mensagem não. No chamado inexistente, o Laravel responde *"No query results for model [App\Models\Chamado] 999"*, inclusive em produção. No negado, responde *"Not Found"*. A diferença na mensagem entregaria o que o status esconde.

## Consequências

- Quem errou de conta não recebe um "sem permissão" claro, só um "não encontrado".
- A mensagem de 404 deixa de dizer qual recurso faltou, para qualquer rota da API.

## Como é verificado

- `ChamadoTest::test_chamado_de_outra_pessoa_responde_igual_a_inexistente`
- `ci.yml`: *Solicitante abre um chamado e não vê o de outra pessoa*, que compara os dois corpos pela rede, byte a byte
