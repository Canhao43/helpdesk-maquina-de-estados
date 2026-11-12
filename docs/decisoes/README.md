# Registro de decisões

Cada decisão de arquitetura do projeto, num arquivo só, com o mesmo modelo: **contexto**, **decisão**, **alternativas descartadas**, **consequências** e **como é verificado**, que aponta o teste ou a etapa do CI que prova a decisão.

O registro é conferido pelo build: `RegistroDeDecisoesTest` falha se a numeração tiver buraco, se faltar uma seção, se um ADR não estiver aqui ou se um teste ou etapa citados não existirem.

Uma decisão que mudar ganha um ADR novo que substitui o antigo. O antigo não some.

## M1 — login, papéis e chamados

- [0001 — Chamado de outra pessoa responde 404, com o mesmo corpo de um inexistente](0001-chamado-alheio-responde-404.md)
- [0002 — Quem vê o quê na listagem é filtrado na consulta](0002-visibilidade-filtrada-na-consulta.md)
- [0003 — O dono do chamado vem do token; papel, dono e estado ficam fora do fillable](0003-dono-do-chamado-vem-do-token.md)
- [0004 — O login não entrega quais e-mails têm conta, nem pela mensagem nem pelo tempo](0004-login-nao-entrega-quais-emails-existem.md)
- [0005 — Token de 7 dias e limite de login por e-mail + IP](0005-token-com-validade-e-limite-de-tentativas.md)
- [0006 — Os dados de demonstração se recusam a rodar em produção](0006-dados-de-demonstracao-recusados-em-producao.md)
- [0007 — SQLite, com espera de 5 s quando o banco está travado](0007-sqlite-com-espera.md)

## M2 — máquina de estados

- [0008 — Máquina de estados escrita à mão: a tabela no enum, a permissão na policy](0008-maquina-de-estados-escrita-a-mao.md)
- [0009 — Cada transição é um recurso: POST /chamados/{id}/acoes/{acao}](0009-transicao-como-recurso.md)
- [0010 — A ordem das respostas: 404, 422, 403, 422 de comentário e 409](0010-ordem-das-respostas.md)
- [0011 — Gravação condicional à versão do chamado, e não ao estado](0011-gravacao-condicional-a-versao.md)

## M3 — histórico e tokens com escopo

- [0012 — Histórico imutável no model e no banco](0012-historico-imutavel-no-banco.md)
- [0013 — O evento é gravado na mesma transação da mudança de estado](0013-evento-na-mesma-transacao.md)
- [0014 — Comentário obrigatório nas ações que mudam a conversa](0014-comentario-onde-a-conversa-muda.md)
- [0015 — O escopo do token é teto, e é checado antes da busca pelo id](0015-escopo-do-token-e-teto.md)

## M4 — SLA

- [0016 — O SLA é calculado do histórico, por uma classe pura](0016-sla-calculado-do-historico.md)
- [0017 — O que conta no relógio do SLA](0017-o-que-conta-no-relogio.md)
- [0018 — A listagem não faz uma consulta por chamado](0018-listagem-sem-consulta-por-chamado.md)

## Todos os marcos

- [0019 — O CI mede pela rede, com o servidor no ar](0019-ci-que-mede.md)
