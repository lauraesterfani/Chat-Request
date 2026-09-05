# Instruções do projeto Chat Request

## Validação obrigatória do front-end

Sempre que uma tarefa criar, remover ou modificar comportamento perceptível no front-end, o agente principal deve acionar o subagente `frontend_qa` depois da implementação.

O agente deve examinar o diff, iniciar o ambiente de teste, executar os fluxos alterados em navegador real, testar desktop e mobile, verificar console e rede e registrar evidências. A tarefa não pode ser considerada totalmente concluída quando o front-end não inicia, a mudança não aparece na tela, há erros inesperados ou um fluxo alterado não foi testado.

O relatório deve ser incluído na entrega final. Se o agente estiver indisponível, o agente principal executará a mesma lista diretamente e registrará a limitação.
