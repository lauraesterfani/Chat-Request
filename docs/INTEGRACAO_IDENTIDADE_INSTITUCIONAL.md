# Integração de identidade institucional

Não há contrato, credencial, escopo, ambiente de testes ou autorização do IFPE para uma integração institucional neste repositório. Por isso, a integração permanece desativada por padrão e o login atual não foi alterado.

## Contrato técnico preparado

`App\Services\InstitutionalIdentity\InstitutionalIdentityProvider` é o ponto de adaptação para um provedor autorizado. Ele recebe `issuer` e `subject` estáveis; implementações não podem vincular contas automaticamente por nome ou e-mail.

Quando `INSTITUTIONAL_IDENTITY_ENABLED=false` (padrão), a fábrica devolve `DisabledInstitutionalIdentityProvider`, que falha de forma explícita e sem fazer chamada de rede. Se a flag for ligada sem um adaptador homologado, a fábrica também falha com segurança. Os testes unitários cobrem essas duas situações.

## O que a TI/IFPE precisa fornecer antes de implementar um adaptador

1. Protocolo aprovado (por exemplo, OIDC), emissor, audience, chaves e ambiente de homologação.
2. Escopos mínimos, finalidade de cada atributo e política de cache/revogação.
3. Identificador estável do provedor e processo de vinculação de contas existentes sem usar e-mail ou nome como chave.
4. Limites, timeout, tratamento de indisponibilidade e contato responsável.
5. Confirmação explícita sobre SSO, SUAP e se qualquer escrita externa é autorizada.

Nenhum token, senha, URL privada ou dado de estudante deve ser incluído em `.env.example`, fixtures ou logs. Um simulador local futuro deve permanecer identificado como simulador e não será evidência de integração real.
