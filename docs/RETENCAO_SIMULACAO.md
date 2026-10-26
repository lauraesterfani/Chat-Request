# Retenção e descarte — matriz para simulação e aprovação

**Sem política aprovada. Nenhum prazo abaixo está ativo e nenhuma exclusão deve ser executada.** O sistema não deve transformar a referência de 90 dias do formulário em prazo de retenção.

| Categoria | Marco inicial a deliberar | Prazo aprovado | Ação proposta para análise | Dependências/hold |
|---|---|---|---|---|
| Requerimento, decisões e documentos | Encerramento do processo, transferência, conclusão acadêmica ou marco definido pela tabela de temporalidade | Pendente | Preservar/arquivar; anonimização ou eliminação somente após autorização | Prova acadêmica, recurso, obrigação de guarda, auditoria |
| Anexos com dados de saúde | Marco específico por serviço e finalidade | Pendente | Avaliar necessidade e acesso restrito; não apagar automaticamente | Obrigação acadêmica/arquivística e contestação |
| Mensagens e pareceres | Encerramento do processo ou prazo próprio | Pendente | Preservar junto ao processo; revisar minimização de notas internas | Controvérsia e processo administrativo |
| Rascunhos e anexos temporários | Última atividade confirmada ou descarte voluntário | Pendente | Arquivamento temporário ou eliminação após aprovação | Rascunho válido, upload órfão, disputa do titular |
| Logs de auditoria e segurança | Geração do evento | Pendente | Acesso restrito e retenção proporcional | Investigação em andamento e obrigação institucional |
| Notificações e filas | Leitura, conclusão ou expiração de job | Pendente | Limpar metadados operacionais após prazo aprovado | Job ainda processável; reconciliação após restore |
| Exports e arquivos derivados | Geração/entrega do arquivo | Pendente | Expirar em storage privado após prazo aprovado | Revisão de acesso, reenvio e obrigação de guarda |
| Backups e cópias | Geração do snapshot | Pendente | Rotação e expiração cifradas conforme política aprovada | Holds, chaves de cifragem, restauração e reaparecimento de exclusões |

## Procedimento de aprovação

1. Gestor do processo e arquivo definem marco, prazo, exceções e consequência operacional para cada categoria.
2. Encarregado/área jurídica avalia base, minimização, direitos e eventuais dados sensíveis ou de menores.
3. TI confirma inventário de bancos, anexos, logs, filas, cache, exportações, backups, destino e chaves.
4. Somente depois, o responsável institucional aprova a política versionada e os responsáveis autorizados para uma execução concreta.
5. A primeira versão operacional deve executar somente simulação, listar contagens e referências mínimas por classe/categoria e reportar holds; não inclui conteúdo documental.
6. Qualquer descarte posterior exige dupla confirmação, trilha de aprovação, execução em lote revisável e procedimento separado de backup/restauração. Não reutilizar o endpoint de limpeza de órfãos para retenção.

## Modelo de execução que permanece desativado

O futuro simulador deve ser somente leitura, não agendar jobs destrutivos e retornar `policy_version`, `generated_at`, critérios, contagens, IDs pseudonimizados mínimos e itens protegidos por hold. Nenhuma política pode ser aprovada ou executada sem ator autorizado, justificativa e referência documental. A ausência de prazo é sempre `pendente`, nunca zero dias ou autorização para excluir.
