import { expect, test } from "@playwright/test";

test("envia e recarrega um requerimento usando API e banco isolados reais", async ({ page, request }, testInfo) => {
  test.skip(!process.env.PHASE7_API_ORIGIN, "Execute com scripts/run-phase7-api-e2e.sh para usar a API e o SQLite isolados.");
  const apiOrigin = process.env.PHASE7_API_ORIGIN!;
  const enrollment = testInfo.project.name.includes("mobile") ? "20241ADMIG002" : "20241TSIIG001";
  const subject = `E2E QA — Histórico 2026 ${testInfo.project.name}`;
  const login = await request.post(`${apiOrigin}/api/login`, {
    data: { matricula: enrollment, password: "ChatRequest-Teste2026!" },
  });
  expect(login.ok(), `Login de QA falhou com HTTP ${login.status()}`).toBeTruthy();
  const token = (await login.json()).token as string;
  expect(token).toBeTruthy();

  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.addInitScript((sessionToken) => sessionStorage.setItem("jwt_token", sessionToken), token);
  await page.goto("/requests/new");

  await page.getByLabel("Tipo de solicitação").selectOption({ label: "Justificativa de Falta / 2ª Chamada" });
  await page.locator("#form-term").fill("2026.2");
  await page.getByRole("button", { name: "Adicionar assunto" }).click();
  await page.getByLabel("Assunto 1").fill("Cálculo I");
  await page.locator("#form-subjects").getByLabel("Descrição", { exact: true }).fill("Avaliação perdida durante afastamento.");
  await page.locator("#form-subject").fill(subject);
  await page.locator("#form-description").fill("Solicitação fictícia para validar a persistência da Fase 7.");
  await expect(page.getByText(/Rascunho salvo/)).toBeVisible({ timeout: 10_000 });
  await page.locator("#request-document").setInputFiles({
    name: "atestado-qa.pdf",
    mimeType: "application/pdf",
    buffer: Buffer.from("%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n"),
  });
  await expect(page.getByText("1 anexo salvo")).toBeVisible({ timeout: 10_000 });
  await page.getByRole("button", { name: "Revisar requerimento" }).click();
  await expect(page.getByRole("heading", { name: "Revise seu requerimento" })).toBeVisible();
  await page.getByRole("button", { name: "Confirmar envio" }).click();

  await expect(page).toHaveURL(/\/requests\/visualizar\/[0-9a-f-]+$/i, { timeout: 15_000 });
  const requestId = page.url().split("/").at(-1)!;
  await expect(page.getByText("Solicitação fictícia para validar a persistência da Fase 7.")).toBeVisible();
  await expect(page.getByText("atestado-qa.pdf")).toBeVisible();
  await expect(page.getByText(/\d{8}-\d{6}/)).toBeVisible();

  const persisted = await request.get(`${apiOrigin}/api/requests/${requestId}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  expect(persisted.ok()).toBeTruthy();
  const persistedRequest = await persisted.json();
  expect(persistedRequest).toMatchObject({
    id: requestId,
    subject,
    description: "Solicitação fictícia para validar a persistência da Fase 7.",
  });
  expect(persistedRequest.form_responses).toMatchObject({
    term: "2026.2",
    subjects: [{ subject: "Cálculo I", description: "Avaliação perdida durante afastamento." }],
  });
  expect(persistedRequest.documents).toEqual(expect.arrayContaining([expect.objectContaining({ name: "atestado-qa.pdf" })]));

  await page.reload();
  await expect(page.getByText("Solicitação fictícia para validar a persistência da Fase 7.")).toBeVisible();

  const authHeaders = { Authorization: `Bearer ${token}` };
  const beforeExternalAttempt = await request.get(`${apiOrigin}/api/requests`, { headers: authHeaders });
  expect(beforeExternalAttempt.ok()).toBeTruthy();
  const requestsBefore = await beforeExternalAttempt.json();
  const typesResponse = await request.get(`${apiOrigin}/api/type-requests`, { headers: authHeaders });
  expect(typesResponse.ok()).toBeTruthy();
  const requestTypes = await typesResponse.json();
  const externalType = requestTypes.find((type: { id: string; name: string }) => type.name === "Serviço externo QA Fase 7");
  expect(externalType?.id).toBeTruthy();

  const externalAttempt = await request.post(`${apiOrigin}/api/requests`, {
    headers: { ...authHeaders, "Idempotency-Key": "phase7-external-channel-0001" },
    data: {
      type_id: externalType.id,
      subject: "Tentativa fictícia de canal externo",
      description: "A API deve orientar o canal oficial sem gerar requerimento local.",
    },
  });
  expect(externalAttempt.status()).toBe(422);
  expect(await externalAttempt.json()).toMatchObject({
    external_channel: true,
    channel_instructions: "Use o canal oficial indicado pela CRADT fictícia do teste.",
  });
  const afterExternalAttempt = await request.get(`${apiOrigin}/api/requests`, { headers: authHeaders });
  expect(afterExternalAttempt.ok()).toBeTruthy();
  expect(await afterExternalAttempt.json()).toHaveLength(requestsBefore.length);

  const concurrentType = requestTypes.find((type: { id: string; name: string }) => type.name === "Serviço digital concorrente QA Fase 7");
  expect(concurrentType?.id).toBeTruthy();
  const concurrentPayload = {
    type_id: concurrentType.id,
    subject: `Envio concorrente ${testInfo.project.name}`,
    description: "Dois retries simultâneos devem gerar um único requerimento.",
  };
  const concurrentKey = "phase7-concurrent-key-0001";
  const concurrentResponses = await Promise.all([1, 2].map(() => request.post(`${apiOrigin}/api/requests`, {
    headers: { ...authHeaders, "Idempotency-Key": concurrentKey },
    data: concurrentPayload,
  })));
  expect(concurrentResponses.map((response) => response.status()).sort()).toEqual([200, 201]);
  const concurrentBodies = await Promise.all(concurrentResponses.map((response) => response.json()));
  expect(concurrentBodies[0].id).toBe(concurrentBodies[1].id);
  const afterConcurrentSubmissions = await request.get(`${apiOrigin}/api/requests`, { headers: authHeaders });
  expect(afterConcurrentSubmissions.ok()).toBeTruthy();
  expect((await afterConcurrentSubmissions.json()).filter((item: { subject: string }) => item.subject === concurrentPayload.subject)).toHaveLength(1);

  expect(errors, errors.join("\n")).toEqual([]);
});

test("retoma entre celular e computador com disciplinas e anexo preservados após expiração da sessão", async ({ browser, request }, testInfo) => {
  test.skip(!process.env.PHASE7_API_ORIGIN, "Execute com scripts/run-phase7-api-e2e.sh para usar a API e o SQLite isolados.");
  const apiOrigin = process.env.PHASE7_API_ORIGIN!;
  const enrollment = testInfo.project.name.includes("mobile") ? "20241ADMIG002" : "20241TSIIG001";
  const subject = `Rascunho entre dispositivos ${testInfo.project.name}`;
  const login = await request.post(`${apiOrigin}/api/login`, {
    data: { matricula: enrollment, password: "ChatRequest-Teste2026!" },
  });
  expect(login.ok(), `Login de QA falhou com HTTP ${login.status()}`).toBeTruthy();
  const token = (await login.json()).token as string;
  const mobileContext = await browser.newContext({ baseURL: process.env.PLAYWRIGHT_BASE_URL, viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const mobile = await mobileContext.newPage();
  await mobile.addInitScript((sessionToken) => sessionStorage.setItem("jwt_token", sessionToken), token);
  await mobile.goto("/requests/new");
  await mobile.getByLabel("Tipo de solicitação").selectOption({ label: "Justificativa de Falta / 2ª Chamada" });
  await mobile.locator("#form-term").fill("2026.2");
  await mobile.getByRole("button", { name: "Adicionar assunto" }).click();
  await mobile.getByLabel("Assunto 1").fill("Física I");
  await mobile.locator("#form-subjects").getByLabel("Descrição", { exact: true }).fill("Avaliação pendente por afastamento.");
  await mobile.locator("#form-subject").fill(subject);
  await mobile.locator("#form-description").fill("Rascunho de teste entre dispositivos.");
  await expect(mobile.getByText(/Rascunho salvo/)).toBeVisible({ timeout: 10_000 });
  await mobile.locator("#request-document").setInputFiles({
    name: "atestado-cross-device.pdf",
    mimeType: "application/pdf",
    buffer: Buffer.from("%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n"),
  });
  await expect(mobile.getByText("1 anexo salvo")).toBeVisible({ timeout: 10_000 });
  await expect(mobile.getByText(/Rascunho salvo/)).toBeVisible({ timeout: 10_000 });

  const ownDrafts = await request.get(`${apiOrigin}/api/drafts`, { headers: { Authorization: `Bearer ${token}` } });
  expect(ownDrafts.ok()).toBeTruthy();
  const draft = (await ownDrafts.json()).find((item: { responses?: { subject?: string } }) => item.responses?.subject === subject);
  expect(draft).toBeTruthy();
  expect(draft).toMatchObject({
    responses: { term: "2026.2", subjects: [{ subject: "Física I", description: "Avaliação pendente por afastamento." }] },
    attached_documents: [expect.objectContaining({ name: "atestado-cross-device.pdf" })],
  });

  const expiredSession = await request.get(`${apiOrigin}/api/drafts`, { headers: { Authorization: "Bearer token-expirado-inválido" } });
  expect(expiredSession.status()).toBe(401);
  const afterExpiredSession = await request.get(`${apiOrigin}/api/drafts`, { headers: { Authorization: `Bearer ${token}` } });
  expect(afterExpiredSession.ok()).toBeTruthy();
  expect((await afterExpiredSession.json()).some((item: { id: string }) => item.id === draft.id)).toBeTruthy();

  const desktopContext = await browser.newContext({ baseURL: process.env.PLAYWRIGHT_BASE_URL, viewport: { width: 1440, height: 900 } });
  const desktop = await desktopContext.newPage();
  await desktop.addInitScript((sessionToken) => sessionStorage.setItem("jwt_token", sessionToken), token);
  await desktop.goto("/requests/drafts");
  const draftCard = desktop.locator("li").filter({ hasText: subject });
  await expect(draftCard).toBeVisible();
  await draftCard.getByRole("link", { name: "Continuar" }).click();
  await expect(desktop).toHaveURL(new RegExp(`/requests/new\\?draft=${draft.id}`));
  await expect(desktop.locator("#form-subject")).toHaveValue(subject);
  await expect(desktop.locator("#form-term")).toHaveValue("2026.2");
  await expect(desktop.getByLabel("Assunto 1")).toHaveValue("Física I");
  await expect(desktop.getByText("Anexo salvo: atestado-cross-device.pdf")).toBeVisible();
  await mobileContext.close();
  await desktopContext.close();
});
