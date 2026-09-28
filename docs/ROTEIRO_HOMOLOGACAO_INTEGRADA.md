# Roteiro de homologação integrada

Este roteiro orienta a validação em ambiente isolado com dados fictícios. Ele não autoriza produção, criação de contas reais, envio de e-mail real, mudança de DNS nem integração institucional.

## Preparação e evidências

Registre antes de iniciar: commit, ambiente, data/hora e responsáveis. Use contas fictícias separadas para aluno, CRADT, coordenação, administração e perfil sem acesso. Não inclua token, CPF, matrícula real, anexo acadêmico ou conteúdo integral de chat nas evidências.

Para cada cenário, registre **passou**, **falhou**, **bloqueado** ou **não executado**, o responsável, uma referência de evidência segura e o número do incidente quando houver. Reexecute os cenários afetados após qualquer correção.

## Fluxo de atendimento

| Cenário | Perfil | Passos com dados fictícios | Resultado esperado |
| --- | --- | --- | --- |
| Login e primeiro acesso | Aluno | Entrar com matrícula de teste que exige troca de senha; trocar senha; entrar novamente | A senha inicial não dá acesso aos pedidos; após a troca, somente a conta autenticada acessa seus dados |
| Serviço digital | Aluno | Consultar catálogo; escolher serviço digital; preencher formulário e anexar arquivo permitido | O pedido recebe protocolo único, snapshot de formulário e anexo privado vinculado ao aluno |
| Serviço externo | Aluno | Consultar item configurado para canal externo | A orientação é exibida sem criar protocolo local |
| Janela de abertura | Aluno | Tentar enviar antes, durante e depois de uma janela publicada de teste | Somente o envio dentro da janela é aceito; o rascunho permanece disponível quando o envio é bloqueado |
| Chat e template | CRADT e aluno, em sessões distintas | CRADT seleciona uma resposta pré-configurada, revisa e envia; aluno atualiza a página e responde | A resposta aparece no histórico para as partes autorizadas; template não é enviado automaticamente |
| Documento pendente | CRADT e aluno | Solicitar complemento por mensagem; aluno substitui documento permitido | O histórico preserva as mensagens; usuário alheio não acessa o anexo |
| Encaminhamento e decisão | CRADT e coordenação autorizada | Encaminhar com motivo, registrar parecer e decisão | Setor, evento, autor e justificativa são preservados; decisão não apaga o histórico |
| Encerramento e satisfação | Aluno | Abrir pedido concluído e responder a pesquisa uma vez | Histórico permanece em leitura; a pesquisa é opcional e não aceita repetição |

## Segurança e permissões

| Cenário | Perfil | Resultado esperado |
| --- | --- | --- |
| Requerimento de outra pessoa | Aluno | API e interface negam acesso sem revelar título, documento ou mensagem |
| Curso não autorizado | Coordenação | Não lista nem abre pedido de curso fora do escopo |
| Perfil técnico | Staff/TI | Não recebe acesso acadêmico implícito a pedidos ou anexos |
| Link direto de exportação ou notificação | Perfil sem acesso | Acesso é revalidado e o conteúdo não é exposto |
| Revogação de escopo | Administração e perfil revogado | O acesso cessa também para consultas previamente disponíveis |

## Resiliência e experiência

| Cenário | Passos | Resultado esperado |
| --- | --- | --- |
| Duas abas/sessões | Abrir o mesmo pedido em duas abas; enviar chat e atualizar | Não há duplicação de mensagem, protocolo ou notificação |
| Sessão expirada | Expirar token durante envio e durante navegação | Mensagem clara; não há perda silenciosa nem exposição de outro perfil |
| API indisponível | Simular resposta 5xx no catálogo, chat e upload | Estado de erro compreensível; a interface não mostra lista vazia como sucesso |
| Upload falho | Simular arquivo inválido ou falha de rede | Erro associado ao envio; pedido não fica vinculado a arquivo de outra pessoa |
| Teclado e tela estreita | Percorrer login, catálogo, pedido e chat com Tab/Enter em 320 px e desktop | Foco visível, sem armadilha de teclado ou overflow horizontal; erros possuem texto associado |

## Operação e recuperação

1. Execute `php artisan test`, TypeScript, build e os smoke tests disponíveis no ambiente.
2. Verifique migrations pendentes e a saúde da fila sem rodar comandos destrutivos.
3. Simule falha de transporte de e-mail usando capturador local; a notificação interna e o pedido devem permanecer íntegros.
4. Execute restauração somente em ambiente isolado e registre RPO/RTO observados sem apresentá-los como metas aprovadas.
5. Compare indicadores e pesquisa de satisfação com fixtures calculáveis; dados ausentes devem aparecer como indisponíveis, não como zero.

## Critério de encerramento do aceite

Uma falha de autorização, protocolo duplicado, anexo público, perda de histórico ou decisão incorreta é **NO GO**. Itens institucionais — serviços do piloto, calendário, documentos alternativos, papéis, privacidade, retenção, integração, backups e responsáveis — só podem ser aprovados pelas áreas competentes do IFPE.
