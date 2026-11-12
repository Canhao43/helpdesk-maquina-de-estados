# 0015 — O escopo do token é teto, e é checado antes da busca pelo id

**Marco:** M3

## Contexto

Um sistema de monitoração que abre chamado sozinho precisa de um token. Um token com tudo o que o dono pode fazer seria um risco desnecessário: se vazar, lê a fila inteira.

## Decisão

- Três escopos do Sanctum: `chamados:ler`, `chamados:abrir` e `chamados:agir`, um por grupo de rotas.
- No login, `escopos` é opcional. Sem ele, o token vale `*`, e quem limita é o papel do dono.
- O escopo é um **teto**: limita o token, mas nunca dá a ele mais do que o papel permite. Um token de admin só com `chamados:abrir` não lê; um token de solicitante com `chamados:agir` não assume.
- O middleware do escopo roda **antes** do `SubstituteBindings` (`prependToPriorityList` em `bootstrap/app.php`).

## Alternativas descartadas

- **Um papel novo, "integração":** misturaria quem a pessoa é com o que o token pode fazer.
- **Deixar a ordem padrão dos middlewares:** a busca pelo id vem antes, e um token sem leitura receberia `404` num id inexistente e `403` num existente. A diferença diria quais chamados existem.
- **Escopo obrigatório no login:** atrito para a pessoa que só quer usar o sistema.

## Consequências

- Um token comum que vaze vale tudo o que o papel do dono vale, por 7 dias.

## Como é verificado

- `EscopoTest::test_token_que_so_abre_nao_le_nem_age`
- `EscopoTest::test_escopo_nao_da_ao_token_mais_do_que_o_papel`
- `EscopoTest::test_sem_leitura_existente_e_inexistente_respondem_igual`
- `EscopoTest::test_sem_escopos_o_token_pode_tudo_o_que_o_papel_deixa`
- `ci.yml`: *Token que só abre chamado não lê nenhum*
