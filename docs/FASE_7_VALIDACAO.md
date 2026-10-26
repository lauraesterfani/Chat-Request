# Fase 7 — evidências e pendências

Status: **núcleo técnico implementado e testado localmente; homologação institucional pendente**.

## Critérios verificados

| Requisito | Evidência | Resultado |
|---|---|---|
| Schema publicado e imutável, revisão, validação de campos condicionais | `tests/Feature/DynamicFormTest.php`, `tests/Unit/FormSchemaValidatorTest.php` | Passou |
| Rascunho privado, propriedade, revisão otimista, anexos e migração de schema | `tests/Feature/DynamicFormTest.php` | Passou |
| Retry técnico e conflito de chave idempotente | `tests/Feature/DynamicFormTest.php` | Passou |
| Chave de envio delimitada por aluno, proteção contra colisão e rollback seguro | `2026_10_26_000009_scope_request_idempotency_key_per_user.php` + `test_submission_idempotency_key_is_scoped_to_the_authenticated_student` | Passou; dois alunos podem usar a mesma chave e cada retry retorna seu próprio pedido. O rollback não remove o índice se isso tornar chaves de alunos distintos ambíguas |
| Aviso padrão de duplicidade, confirmação, bloqueio excepcional e isolamento por aluno | `tests/Feature/DuplicateRequestPreventionTest.php` | Passou |
| Ocultação/remoção de resposta condicional com possibilidade de desfazer | `frontend/e2e/conditional-field-discard.spec.ts` | Passou em desktop e mobile |
| Fluxo de aviso e link para pedido próprio | `frontend/e2e/duplicate-warning.spec.ts` | Passou em desktop e mobile |
| Configuração administrativa da política em tela pequena | `frontend/e2e/duplicate-policy-admin.spec.ts` e relatório `frontend_qa` | Passou em desktop e mobile |
| Envio, upload e vínculo de anexo privado, gravação no banco/storage isolados, protocolo e recarga dos detalhes | `scripts/run-phase7-api-e2e.sh` + `frontend/e2e/phase7-api-flow.spec.ts` | Passou em Chromium desktop e mobile |
| Dois POSTs simultâneos com a mesma chave produzem um único requerimento e protocolo | `scripts/run-phase7-api-e2e.sh` executa Laravel com workers concorrentes; `frontend/e2e/phase7-api-flow.spec.ts` dispara ambas as requisições em paralelo | Passou contra API e SQLite temporários em desktop e mobile; uma resposta foi 201 e a outra replay 200 com o mesmo ID |
| Formulário com lista dinâmica de disciplinas, rascunho retomado entre celular e computador, documento privado e preservação após sessão expirada | `scripts/run-phase7-api-e2e.sh` + `frontend/e2e/phase7-api-flow.spec.ts` | Passou; retomada desktop depois de início móvel e sessão inválida testada contra API real |
| Falha de rede no autosalvamento, preservação dos campos e nova tentativa após reconexão | `frontend/e2e/draft-network-recovery.spec.ts` | Passou em desktop e mobile; o teste confere que a única requisição falha é o POST de rascunho simulado e que a tentativa seguinte persiste os dados |

O teste com API real usa SQLite e storage novos em `/tmp`, contas `.test` separadas para os projetos desktop e mobile, anexo PDF fictício e dados de pedido sintéticos. Não acessa o banco/storage da aplicação configurado pelo usuário. Os testes padrão usam os mocks descritos nos próprios specs; o teste API-backed só roda pelo script dedicado.

## Contrato de duplicidade

- `POST /api/requests/duplicates/check`: recebe serviço, assunto e matrícula opcional; retorna no máximo cinco correspondências abertas do próprio aluno.
- `POST /api/requests`: revalida possíveis correspondências dentro da transação antes de criar o pedido.
- Por padrão o sistema avisa e permite continuar após confirmação explícita. Bloqueio duro exige modo `block`, referência de regra e administrador institucional irrestrito. A janela opcional considera todos os pedidos abertos quando não configurada.
- Esta comparação é determinística por aluno, serviço, vínculo e assunto normalizado; não pretende inferir equivalência semântica de pedidos.

## Pendências que dependem de confirmação institucional

- A lista oficial contém 33 serviços no formulário de referência, mas apenas 15 tipos estão no seeder atual. Não foram ativados serviços adicionais porque o PDF não homologa disponibilidade, requisitos, canais nem regras para produção.
- Não há decisão institucional fornecida sobre regras específicas de duplicidade por disciplina, semestre, via de documento ou finalidade. O modo padrão continua sendo aviso; nenhuma regra de bloqueio deve ser ativada sem homologação real.
- Elegibilidade de pessoas graduadas/desvinculadas e serviços permitidos para cada situação não possui uma matriz oficial no repositório. O acesso ao próprio histórico é preservado; a elegibilidade para novos requerimentos precisa de decisão institucional.
- O exercício API-backed `scripts/run-phase7-api-e2e.sh` envia e recarrega o detalhe com um PDF fictício em SQLite e storage temporários; o arquivo aparece vinculado no detalhe e na API. Limpeza e retenção de longo prazo continuam dependendo da política da Fase 9.
- A publicação de versões de formulários continua sendo função técnica; os schemas acadêmicos existentes precisam de aprovação dos responsáveis antes de uso real.

## Comandos verificados nesta implementação

- `php artisan test`: 173 testes passaram (1.244 assertions) na rodada completa mais recente.
- `php artisan test --filter=DynamicFormTest`: 24 testes passaram (140 assertions), incluindo isolamento por aluno, rejeição de chave repetida pela restrição do banco e rollback protegido contra colisões.
- `scripts/run-phase7-api-e2e.sh`: 4 execuções passaram (2 fluxos em desktop e 2 em mobile) com API, SQLite e storage isolados reais; o fluxo de envio inclui duas requisições simultâneas com mesma chave e valida a resposta única.
- `frontend/e2e/draft-network-recovery.spec.ts`: 2/2 passaram em desktop/mobile; texto permaneceu no formulário durante a queda simulada e foi salvo após nova edição.
- O endpoint bloqueia protocolo local para serviço configurado como externo mesmo após a entrada deixar de estar publicada; retries de uma criação confirmada continuam idempotentes.
- `npx tsc --noEmit`: passou.
- `vendor/bin/pint --test` nos arquivos PHP desta mudança: passou.
- E2E direcionado ao aviso e à configuração: 4/4 passaram segundo `frontend_qa` após correção de viewport; E2E de remoção condicional: 2/2 passaram.
- A rodada completa registrada na validação da Fase 12 passou com 64 testes e 6 skips explicitamente condicionados a integrações externas ausentes. Isso não cobre homologação institucional, integração real nem todos os cenários enumerados no prompt original da Fase 7.
