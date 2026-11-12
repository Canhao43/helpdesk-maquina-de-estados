# 0005 — Token de 7 dias e limite de login por e-mail + IP

**Marco:** M1

## Contexto

O token do Sanctum, por padrão, não vence. E o login sem limite aceita tentativas de senha à vontade.

## Decisão

- Todo token nasce com validade de 7 dias (`expires_at`). Sair (`DELETE /api/tokens/atual`) revoga o token na hora.
- O login aceita 5 tentativas por minuto, com a chave **e-mail + IP**.

## Alternativas descartadas

- **Token sem validade:** se vazar, vale até alguém lembrar de revogar.
- **Limite só por IP:** um escritório inteiro, atrás do mesmo IP, pagaria pelos erros de uma pessoa.
- **Limite só por e-mail:** o atacante trancaria a vítima errando a senha dela de propósito.

## Consequências

- Ataque distribuído por muitos IPs contra uma conta não é contido.
- Token vencido continua na tabela até alguém rodar `php artisan sanctum:prune-expired`.

## Como é verificado

- `LoginTest::test_token_vencido_nao_entra`
- `LoginTest::test_sexta_tentativa_no_mesmo_minuto_e_barrada`
- `LoginTest::test_sair_revoga_o_token`
