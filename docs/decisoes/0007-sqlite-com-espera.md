# 0007 — SQLite, com espera de 5 s quando o banco está travado

**Marco:** M1 e M2

## Contexto

Quem clona o projeto deve rodar em um minuto, sem servidor de banco. Mas, a partir do M2, a API tem de aguentar escrita concorrente: dez atendentes no mesmo chamado.

## Decisão

- SQLite em arquivo para rodar e em memória para os testes.
- `busy_timeout` de 5 s: quem encontra o banco travado por uma escrita espera, em vez de falhar na hora com *"database is locked"*.
- A proteção contra corrida não depende do banco: é a gravação condicional do [ADR 0011](0011-gravacao-condicional-a-versao.md), que funciona igual em qualquer um.

## Alternativas descartadas

- **MySQL em Docker:** pediria servidor ou Docker só para provar o que a gravação condicional já garante.

## Consequências

- O SQLite põe as escritas em fila: a vazão de escrita é a de um processo só. Para um helpdesk interno, sobra.
- Os triggers do histórico ([ADR 0012](0012-historico-imutavel-no-banco.md)) usam sintaxe do SQLite. Em MySQL, seriam reescritos.

## Como é verificado

- `ci.yml`: *Dez atendentes tentam assumir o mesmo chamado ao mesmo tempo*, contra um servidor de 10 processos: nenhum `500`
