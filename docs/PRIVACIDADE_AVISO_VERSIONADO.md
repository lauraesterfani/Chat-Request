# Aviso de privacidade versionado

**Estado atual:** fluxo técnico implementado; aviso institucional ainda pendente de validação e publicação pelo IFPE. Este documento descreve o sistema e não constitui o aviso oficial nem parecer jurídico.

## O que o sistema faz

- Mantém versões numeradas do aviso em estados `draft`, `review` e `published`.
- Rascunhos e versões em revisão são visíveis somente a administradores institucionais ativos, globais e sem escopo limitado.
- Uma versão publicada não pode ser editada. A atualização parte de uma cópia em novo rascunho e exige nova revisão, fonte, referência de aprovação e data de vigência.
- A página e a API públicas retornam exclusivamente uma versão publicada que tenha todos os campos institucionais obrigatórios. Enquanto nenhuma versão satisfaz essas condições, a resposta pública é `pending_approval` e a interface informa que os dados institucionais aguardam confirmação.
- A autoria, o responsável pela revisão/publicação, os horários, a fonte, a referência de aprovação e a data de vigência ficam associados à versão.
- O registro de auditoria contém apenas tipo de ação, versão e estado; não armazena o conteúdo do aviso nem informações pessoais adicionais.

## Campos necessários para publicar

O rascunho precisa incluir título e conteúdo. A publicação também exige:

- Identificação confirmada do responsável institucional pelo tratamento.
- Canal oficial de contato.
- Resumo validado das bases legais e finalidades.
- Resumo validado de retenção e descarte.
- Fonte institucional verificável.
- Referência verificável da aprovação institucional.
- Data de vigência.

Os valores não são preenchidos automaticamente. Os exemplos dos testes são fictícios e não devem ser copiados para produção. A referência registrada deve apontar para a aprovação institucional real; a função de administrador no Chat Request não representa, por si só, uma designação de encarregado nem uma aprovação do IFPE.

## Estados e operações

```text
draft ── enviar para revisão ──> review ── publicar após validação ──> published
  ^                               │
  └──── devolver para edição ─────┘
published ── criar nova versão ──> novo draft com número seguinte
```

Uma nova versão herda apenas o texto e os campos de referência geral. Fonte, referência de aprovação e vigência são limpas e precisam ser registradas novamente. A página pública continua exibindo a versão publicada mais recente durante a preparação da próxima versão.

## Interfaces implementadas

- Página pública: `/privacidade`.
- Administração de versões: `/dashboard/admin/privacy-notices`.
- API pública: `GET /api/privacy-notice/current`.
- API administrativa: `GET|POST /api/admin/privacy-notices`, `PUT /api/admin/privacy-notices/{id}` e operações `submit-review`, `return-to-draft`, `publish` e `new-version` no identificador da versão. Essas rotas exigem admin ativo e global sem escopos.
- O endpoint público e as rotas administrativas estão registrados em `routes/api.php`; a suíte usa as rotas reais.

## Aprovações ainda necessárias

Antes de publicar conteúdo real, a instituição precisa confirmar o controlador, o canal, bases e finalidades, prazos, fonte normativa/documental, autoridade que aprova o texto e data de vigência. O sistema não afirma conformidade legal a partir da existência de controles técnicos.
