# Limpeza controlada de uploads privados

`php artisan documents:prune-orphans --older-than-days=30` apenas conta candidatos; não altera arquivos ou registros. O número de dias é um exemplo operacional, **não** uma política de retenção aprovada pelo IFPE. Um operador deve definir o prazo aplicável antes de executar o comando.

O comando considera apenas registros de `documents` antigos que apontam para arquivo existente no disco privado sob `documents/`, sem vínculo com qualquer requerimento ou rascunho — inclusive rascunhos descartados ou enviados. Arquivos públicos legados, caminhos fora do padrão, arquivos ausentes e registros que compartilham o mesmo caminho são preservados. Arquivos sem registro no banco não são tratados por esta rotina.

Para excluir, faça backup verificável do banco e dos anexos, suspenda a aplicação e os workers e examine primeiro o resultado da simulação. Depois, durante a janela de manutenção, use `--delete --confirm=DELETE_UNLINKED_PRIVATE_DOCUMENTS` com o mesmo `--older-than-days` e, se necessário, `--limit` (máximo 1000 por execução). O comando revalida cada candidato antes da exclusão. Não há agendamento automático.

O vínculo em `request_drafts.document_ids` é JSON e não possui chave estrangeira para `documents`. Por isso a aplicação e os workers devem permanecer parados enquanto a opção `--delete` roda; o comando rejeita a exclusão fora de manutenção em ambientes não-testes. Uma falha rara de commit do banco depois de remover o arquivo pode deixar um registro órfão, a ser investigado manualmente. Nenhum prazo institucional ou descarte de dados acadêmicos é inferido por esta ferramenta.
