import { test, expect, type Page } from "@playwright/test";

const apiOrigin = process.env.PHASE8_API_ORIGIN ?? "http://127.0.0.1:18108";
const adminEmail = "qa.admin@example.test";
const cradtEmail = "qa.cradt@example.test";
const password = "ChatRequest-Teste2026!";
const qaCampus = "Campus QA Norte";

test.use({ timezoneId: "America/Sao_Paulo" });

async function login(page: Page, email: string): Promise<string> {
  const response = await page.request.post(`${apiOrigin}/api/login/staff`, { data: { email, password } });
  expect(response.ok(), `login de teste para ${email}`).toBeTruthy();
  return (await response.json()).token as string;
}

async function setToken(page: Page, token: string): Promise<void> {
  await page.addInitScript((value) => sessionStorage.setItem("jwt_token", value), token);
}

test("admin concede acesso temporário por campus, operador não abre outro campus e revogação bloqueia após recarregar", async ({ page }) => {
  test.setTimeout(120_000);
  const adminToken = await login(page, adminEmail);
  const cradtToken = await login(page, cradtEmail);
  const allRequestsResponse = await page.request.get(`${apiOrigin}/api/admin/requests`, { headers: { Authorization: `Bearer ${adminToken}` } });
  expect(allRequestsResponse.ok()).toBeTruthy();
  const allRequests = await allRequestsResponse.json();
  const allowedRequest = allRequests.find((request: { subject: string }) => request.subject === "Pedido autorizado QA Campus Norte");
  const foreignRequest = allRequests.find((request: { subject: string }) => request.subject === "Pedido restrito QA Campus Sul");
  expect(allowedRequest?.id).toBeTruthy();
  expect(foreignRequest?.id).toBeTruthy();
  const consoleErrors: string[] = [];
  const failedRequests: string[] = [];
  const serverErrors: string[] = [];
  page.on("console", (message) => { if (message.type() === "error") consoleErrors.push(message.text()); });
  page.on("requestfailed", (request) => failedRequests.push(`${request.method()} ${request.url()}: ${request.failure()?.errorText}`));
  page.on("response", (response) => { if (response.status() >= 500) serverErrors.push(`${response.status()} ${response.url()}`); });

  await setToken(page, adminToken);
  await page.goto("/dashboard/admin/access-scopes");
  await expect(page.getByRole("heading", { name: "Escopos de acesso" })).toBeVisible();
  await page.getByLabel("Pessoa da equipe").selectOption({ label: "Atendimento CRADT de Teste · CRADT" });
  await page.getByLabel("Campus (opcional)").selectOption({ label: "Campus QA Norte · QA-N" });
  await page.getByLabel("Setor (opcional)").selectOption("CRADT");
  await page.getByLabel("Justificativa").fill("Cobertura temporária fictícia para teste integrado da Fase 8.");
  const scheduledStart = await page.evaluate(() => {
    const date = new Date();
    date.setSeconds(0, 0);
    date.setMinutes(date.getMinutes() + 1);
    const pad = (value: number) => String(value).padStart(2, "0");
    return {
      epoch: date.getTime(),
      value: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`,
    };
  });
  await page.getByLabel("Início do acesso").fill(scheduledStart.value);
  await page.getByRole("button", { name: "Conceder acesso" }).click();
  await expect(page.getByRole("status")).toContainText("Acesso concedido e registrado.");
  await expect(page.getByText(`${qaCampus} · Todos os cursos · CRADT`).first()).toBeVisible();

  await page.waitForTimeout(Math.max(0, scheduledStart.epoch - Date.now() + 5_000));
  await setToken(page, cradtToken);
  await page.goto("/dashboard/admin/queue");
  await expect(page.getByRole("heading", { name: "Fila de atendimentos" })).toBeVisible();
  const allowedRow = page.getByRole("link", { name: /20261026-810001/ });
  await expect(allowedRow).toContainText("Pedido autorizado QA Campus Norte");
  await expect(page.getByText("Pedido restrito QA Campus Sul")).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  if (test.info().project.name.includes("mobile")) {
    await expect(page.getByText(/^Protocolo$/i).last()).toBeVisible();
    await expect(page.getByText(/^Serviço$/i).last()).toBeVisible();
  }
  await page.screenshot({ path: `/tmp/chat-request-phase8-api-queue-${test.info().project.name}.png`, fullPage: true });
  await allowedRow.click();
  await expect(page.getByText("Descrição do Requerimento")).toBeVisible();
  await expect(page.getByText("Registro fictício para validar acesso por campus.")).toBeVisible();

  const foreignDetail = await page.evaluate(async (foreignId) => {
    const response = await fetch(`/api/requests/${foreignId}`, {
      headers: { Authorization: `Bearer ${sessionStorage.getItem("jwt_token")}` },
    });
    return response.status;
  }, foreignRequest.id);
  expect(foreignDetail).toBe(403);

  // Use the admin UI to revoke the grant, keeping the grant lifecycle under test.
  await setToken(page, adminToken);
  await page.goto("/dashboard/admin/access-scopes");
  await expect(page.getByText(`${qaCampus} · Todos os cursos · CRADT`).first()).toBeVisible();
  page.once("dialog", (dialog) => dialog.accept());
  await page.getByRole("button", { name: "Revogar" }).first().click();
  await expect(page.getByRole("status")).toContainText("Acesso revogado");
  await expect(page.getByText("Revogado", { exact: true }).first()).toBeVisible();

  await setToken(page, cradtToken);
  await page.goto("/dashboard/admin/queue");
  await expect(page.getByRole("heading", { name: "Fila de atendimentos" })).toBeVisible();
  await expect(page.getByText("Pedido autorizado QA Campus Norte")).toHaveCount(0);
  await expect(page.getByText("Pedido restrito QA Campus Sul")).toHaveCount(0);
  await expect(page.getByRole("status")).toContainText("Não há pedidos abertos");

  const requestIds = await page.request.get(`${apiOrigin}/api/admin/requests`, { headers: { Authorization: `Bearer ${cradtToken}` } });
  expect(requestIds.ok()).toBeTruthy();
  const visibleRequests = await requestIds.json();
  expect(visibleRequests).toHaveLength(0);

  const detailAfterRevoke = await page.request.get(`${apiOrigin}/api/requests/${allowedRequest.id}`, { headers: { Authorization: `Bearer ${cradtToken}` } });
  expect(detailAfterRevoke.status()).toBe(403);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.screenshot({ path: `/tmp/chat-request-phase8-api-${test.info().project.name}.png`, fullPage: true });
  expect(serverErrors).toEqual([]);
  expect(failedRequests.filter((failure) => !failure.endsWith("net::ERR_ABORTED"))).toEqual([]);
  // The browser reports the deliberately forbidden cross-campus fetch as a
  // resource error; its 403 status is asserted above and is expected here.
  expect(consoleErrors.filter((error) => !error.includes("status of 403 (Forbidden)"))).toEqual([]);
});
