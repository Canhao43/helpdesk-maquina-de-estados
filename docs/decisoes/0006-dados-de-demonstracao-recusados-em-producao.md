# 0006 — Os dados de demonstração se recusam a rodar em produção

**Marco:** M1

## Contexto

O seeder cria um usuário por papel, todos com a senha `password`, para quem clona o projeto testar em um minuto. Em produção, isso seria uma porta com senha conhecida.

## Decisão

- O `DatabaseSeeder` lança exceção se o ambiente for produção.

## Alternativas descartadas

- **Confiar na confirmação do `db:seed` em produção:** ela existe, mas um `--force` num script de deploy passa por cima, e é esse o risco real.
- **Senha aleatória impressa no console:** atrapalha quem só quer clonar e testar.

## Consequências

- Em produção, o primeiro usuário tem de ser criado por fora do seeder.

## Como é verificado

- `DadosDeDemonstracaoTest::test_se_recusa_a_rodar_em_producao`, que roda com `--force`. A primeira versão do teste rodava sem a flag: o comando parava antes de chegar ao seeder, e o teste falhava mesmo com a trava no lugar.
