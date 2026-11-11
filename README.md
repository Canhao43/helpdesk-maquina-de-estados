# Helpdesk com máquina de estados

[![CI](https://github.com/Canhao43/helpdesk-maquina-de-estados/actions/workflows/ci.yml/badge.svg)](https://github.com/Canhao43/helpdesk-maquina-de-estados/actions/workflows/ci.yml)

API em Laravel de um helpdesk interno: quem tem um problema abre um chamado, quem atende trabalha a fila. O centro do projeto é a **máquina de estados** do chamado. Cada mudança de situação é uma transição com regra própria de quem pode fazer, e uma transição proibida nunca chega ao banco.

É um problema genérico, escrito do zero, e todo dado é fictício.

> Construído com agentes de IA (Claude Code) e revisado por mim em cada PR. As regras que os agentes seguem estão em [`CLAUDE.md`](CLAUDE.md).

## Roteiro

| Marco | O que entra | Estado |
|---|---|---|
| **M1** | Login por token, papéis (solicitante, atendente, admin), abrir e consultar chamados | 🟡 em revisão |
| **M2** | Máquina de estados: transição inválida = `422`, sem permissão = `403`, dois atendentes ao mesmo tempo = `409` | — |
| **M3** | Histórico imutável de cada transição; tokens com escopo | — |
| **M4** | SLA por prioridade, com o relógio parado enquanto se espera o solicitante | — |
| **M5** | Registro de decisões e README final | — |

## Como rodar

Precisa de PHP 8.4 e Composer.

```bash
git clone https://github.com/Canhao43/helpdesk-maquina-de-estados.git
cd helpdesk-maquina-de-estados
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

O `--seed` cria três usuários de demonstração, um por papel, todos com a senha `password`: `solicitante@exemplo.test`, `atendente@exemplo.test` e `admin@exemplo.test`. Em produção o seeder se recusa a rodar.

Testes: `php artisan test`.

## A API até aqui

| Método e caminho | Quem | O que faz |
|---|---|---|
| `POST /api/tokens` | qualquer um | Troca e-mail e senha por um token (`Bearer`) que vale 7 dias. Cinco tentativas por minuto por e-mail e IP |
| `DELETE /api/tokens/atual` | logado | Revoga o token usado na requisição |
| `GET /api/eu` | logado | Quem é o dono do token, com o papel |
| `POST /api/chamados` | logado | Abre um chamado. Responde `201` com `Location` |
| `GET /api/chamados` | logado | Lista paginada. O solicitante vê só os próprios; atendente e admin veem todos |
| `GET /api/chamados/{id}` | dono, atendente ou admin | Um chamado. O de outra pessoa responde `404`, igual a um inexistente |

```bash
TOKEN=$(curl -s -X POST localhost:8000/api/tokens -H 'Content-Type: application/json' \
  -d '{"email":"solicitante@exemplo.test","senha":"password"}' | jq -r .token)

curl -s -X POST localhost:8000/api/chamados -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"titulo":"Impressora não imprime","descricao":"A fila trava no primeiro documento."}'
```

## Decisões até aqui

| Decisão | Por quê | Descartado |
|---|---|---|
| **Chamado alheio responde `404`, não `403`** | Com `403`, quem testasse ids em sequência saberia quais existem. Todo `404` da API sai com o mesmo corpo, porque a mensagem padrão do Laravel diria o nome do model num caso e não no outro | `403`, que é "mais correto" e vaza a existência |
| **Quem vê o quê na listagem é filtrado na consulta** (escopo `visiveisPara`) | Filtrar depois de buscar quebraria a paginação, e o total contaria chamados escondidos | Policy item a item na coleção |
| **O dono do chamado é sempre o dono do token** | `solicitante_id`, `atendente_id` e `estado` ficam fora do `fillable`. Mandar esses campos no corpo não muda nada, e há teste disso | Confiar no corpo e validar depois |
| **`papel` fora do `fillable` do usuário** | Nenhum dado vindo de requisição promove alguém a atendente ou admin | — |
| **Token do Sanctum com validade de 7 dias** | Token sem validade é credencial eterna se vazar | Sem validade (o padrão do Sanctum) |
| **Login: mesma resposta e mesmo tempo para e-mail inexistente e senha errada** | Quem tenta não descobre quais e-mails têm conta. Sem conta, o hash não roda e a falha voltaria mais rápido: o relógio entregaria o que a mensagem esconde. A falha leva no mínimo 200 ms (`Timebox`, como no login de sessão do Laravel) | Mensagens diferentes, mais "amigáveis" · comparar com um hash falso, que amarra o tempo ao custo do bcrypt configurado |
| **Limite de login por e-mail + IP** | Trava a força bruta de um IP contra uma conta, e o atacante não bloqueia o login da vítima, que vem de outro IP | Só por IP (bloqueia um escritório inteiro) · só por e-mail (o atacante tranca a vítima) |
| **SQLite** | Um arquivo, sem servidor: quem clona roda em um minuto. Os testes usam SQLite em memória | MySQL, que pediria servidor ou Docker. A escolha é revista no M2, quando entra concorrência |

**Limites conhecidos:** o limite de login não segura ataque distribuído por muitos IPs. Token vencido continua na tabela até alguém rodar `php artisan sanctum:prune-expired`.
