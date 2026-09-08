# Dicionário de indicadores operacionais

Os indicadores desta etapa são operacionais e não avaliam desempenho individual. Eles usam o relógio do servidor e os dados persistidos do requerimento.

| Indicador | Definição | Exclusões | Limitação |
| --- | --- | --- | --- |
| Estoque aberto | requerimentos cujo status não é concluído ou cancelado | concluídos e cancelados | não mede prioridade acadêmica |
| Pendentes | status `pending` | demais etapas | representa etapa, não decisão |
| Em análise | status `analyzing` | demais etapas | depende da transição registrada pela equipe |
| Atrasados | estoque aberto criado antes do limite configurado | concluídos e cancelados | ainda não usa dias úteis ou pausa de SLA |
| Distribuição por etapa | quantidade agrupada pelo status atual | nenhum status conhecido | não substitui linha do tempo |
| Distribuição por curso | quantidade agrupada pelo curso do solicitante | registros sem curso, se existirem | só pode ser exposta em escopo autorizado |

Dados legados sem política de SLA não devem ser apresentados como cumprimento ou descumprimento de prazo.
