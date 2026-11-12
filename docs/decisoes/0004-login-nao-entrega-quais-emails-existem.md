# 0004 — O login não entrega quais e-mails têm conta, nem pela mensagem nem pelo tempo

**Marco:** M1

## Contexto

Quem descobre quais e-mails têm conta tem metade de um ataque de senha pronta. A mensagem é o vazamento óbvio; o tempo é o que passa despercebido. Sem conta, o bcrypt não roda e a falha volta em milissegundos; com conta, leva o tempo do hash, que é caro de propósito.

## Decisão

- E-mail inexistente e senha errada respondem o mesmo `422`, com a mesma mensagem.
- A checagem roda dentro de um `Timebox` de 200 ms: toda falha leva no mínimo esse tempo. O acerto sai na hora (`returnEarly`), porque quem acertou a senha já sabe que a conta existe. É o que o `SessionGuard` do próprio Laravel faz.

## Alternativas descartadas

- **Mensagens diferentes, mais "amigáveis":** entregam a conta.
- **Comparar com um hash falso quando não há conta:** iguala o tempo, mas amarra a igualdade ao custo do bcrypt configurado. Mudou o custo, o hash falso fica fora do passo.

## Consequências

- Toda falha de login custa 200 ms a mais, inclusive a de quem errou a senha de boa-fé.
- O tempo do acerto continua diferente do da falha, de propósito.

## Como é verificado

- `LoginTest::test_senha_errada_e_email_inexistente_dao_a_mesma_resposta`
- `LoginTest::test_falha_de_login_leva_o_tempo_minimo_com_ou_sem_conta`, que testa o limite de baixo (≥ 0,2 s) para não ficar instável
