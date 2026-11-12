# 0003 — O dono do chamado vem do token; papel, dono e estado ficam fora do fillable

**Marco:** M1

## Contexto

Atribuição em massa (*mass assignment*): se o model aceitasse qualquer campo do corpo, um cliente poderia abrir chamado em nome de outra pessoa, já atribuído ou já resolvido. No usuário, poderia se promover a admin.

## Decisão

- `Chamado` aceita só `titulo`, `descricao` e `prioridade`. O solicitante é associado a partir do usuário do token, e o estado inicial é sempre `aberto`.
- `User` não aceita `papel`. Nenhum dado de requisição muda o papel de ninguém.

## Alternativas descartadas

- **Aceitar o corpo e validar depois:** cada campo novo seria uma brecha até alguém lembrar de validar.

## Consequências

- Hoje não há como mudar o papel pela API, só por seeder, `tinker` ou banco. Uma tela de administração teria policy própria.

## Como é verificado

- `ChamadoTest::test_o_dono_do_chamado_e_sempre_quem_esta_logado`, que manda `solicitante_id`, `atendente_id` e `estado` no corpo e confere que nada disso pega
