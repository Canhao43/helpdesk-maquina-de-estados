# 0011 — Gravação condicional à versão do chamado, e não ao estado

**Marco:** M2

## Contexto

Entre ler o chamado e gravar a transição, outra requisição pode gravar antes. Dois atendentes clicando em "assumir" no mesmo instante passariam pelas mesmas checagens. O último a gravar ficaria com o chamado, e o primeiro acharia que ficou.

## Decisão

- O chamado tem uma coluna `versao`, que sobe a cada transição.
- A gravação é `UPDATE ... WHERE id = ? AND versao = (a lida)`. Zero linhas afetadas quer dizer que alguém gravou antes, e a resposta é `409`.

## Alternativas descartadas

- **Comparar o estado lido em vez da versão:** falha no caso A-B-A. O chamado está com a Ana, e ela o lê. Enquanto ela decide, ele é devolvido e a Bia assume, e o estado volta a ser "em atendimento". O estado bate, e a Ana resolveria o chamado da Bia.
- **Trava pessimista (`SELECT ... FOR UPDATE`):** o SQLite não tem, e segurar trava durante a requisição custa vazão.

## Consequências

- Cobre a corrida dentro do servidor. Não cobre o cliente que decidiu olhando uma tela antiga, se a ação ainda valer: isso pediria `If-Match` com a versão que ele viu.

## Como é verificado

- `ConcorrenciaTest::test_dois_atendentes_ao_mesmo_tempo_so_um_fica_com_o_chamado`, que injeta a gravação da Bia no meio da requisição da Ana
- `ConcorrenciaTest::test_estado_igual_ao_lido_nao_basta_se_o_chamado_mudou_no_meio`
- `ci.yml`: *Dez atendentes tentam assumir o mesmo chamado ao mesmo tempo*, que exige um `200` só e um atendente só
