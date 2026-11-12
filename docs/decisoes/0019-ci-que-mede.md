# 0019 — O CI mede pela rede, com o servidor no ar

**Marco:** M1 a M4

## Contexto

Os testes rodam dentro do processo, em SQLite na memória. Eles não provam que a aplicação de verdade funciona: o arquivo do banco, o servidor HTTP, o token passando pelo cabeçalho, nem várias requisições ao mesmo tempo.

## Decisão

- Um segundo job sobe a aplicação com os dados de demonstração e um servidor de **10 processos**, e faz o fluxo por `curl`.
- Cada etapa imprime no log o que mediu (a distribuição das respostas, o corpo comparado byte a byte, o erro do banco), e não só o status.
- O CI só exige o que é determinístico: um `200` só na corrida, um atendente só. Quantos `409` saem depende de sorte, e por isso vai para o log, não para a asserção.

## Alternativas descartadas

- **Só os testes:** passariam com o servidor quebrado.
- **Exigir pelo menos um `409` na corrida:** o teste ficaria instável. O caminho do `409` é garantido pelo teste que injeta a corrida ([ADR 0011](0011-gravacao-condicional-a-versao.md)).

## Consequências

- Duas armadilhas desse job foram achadas lendo o log: o `artisan serve` sem `--no-reload` ignora os workers e sobe um processo só (a primeira "corrida" foi atendida em fila), e um `! comando` não derruba o script com `set -e`. As duas estão corrigidas, e o passo falha se o servidor subir com um processo só.

## Como é verificado

- `ci.yml`: *Sobe o servidor, com 10 processos para atender em paralelo*
- `ci.yml`: *Solicitante abre um chamado e não vê o de outra pessoa*
- `ci.yml`: *Atendente assume pela API, e o solicitante não consegue resolver*
- `ci.yml`: *Dez atendentes tentam assumir o mesmo chamado ao mesmo tempo*
- `ci.yml`: *Histórico pela API, e imutável até para o cliente do banco*
- `ci.yml`: *Token que só abre chamado não lê nenhum*
