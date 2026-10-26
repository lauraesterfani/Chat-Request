import { expect, test } from "@playwright/test";

const initialStudentRequest = {
  id: "privacy-request-1",
  protocol: "PRV-20261026-TEST000001",
  request_type: "access",
  description: "Quero saber quais dados estão associados à minha conta.",
  status: "submitted",
  response_summary: null,
  rejection_reason: null,
  responded_at: null,
  created_at: "2026-10-26T12:00:00.000Z",
};

test("estudante registra uma solicitação de dados e consulta o histórico após recarregar", async ({ page }) => {
  const saved = [initialStudentRequest];
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "privacy-student-token"));
  await page.route("**/api/me", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ id: "student-privacy", name: "Aluna QA", role: "student" }),
  }));
  await page.route("**/api/privacy/data-requests**", async (route) => {
    const request = route.request();
    if (request.method() === "POST") {
      const input = request.postDataJSON() as { request_type: string; description: string };
      const created = {
        ...initialStudentRequest,
        id: "privacy-request-2",
        protocol: "PRV-20261026-TEST000002",
        request_type: input.request_type,
        description: input.description,
      };
      saved.unshift(created);
      return route.fulfill({ status: 201, contentType: "application/json", body: JSON.stringify(created) });
    }
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: saved, current_page: 1, last_page: 1, total: saved.length }) });
  });

  await page.goto("/requests/privacidade");
  await expect(page.getByRole("heading", { name: "Solicitações sobre seus dados" })).toBeVisible();
  await expect(page.getByText("Informação institucional pendente de aprovação")).toBeVisible();
  await page.getByLabel("Tipo de solicitação").selectOption("correction");
  await page.getByLabel("Como podemos analisar seu pedido?").fill("Preciso pedir a análise de um dado cadastral da minha conta.");
  await page.getByRole("button", { name: "Registrar solicitação" }).click();
  await expect(page.getByText(/Solicitação registrada\. Guarde o protocolo PRV-20261026-TEST000002/)).toBeVisible();
  await expect(page.getByText("Preciso pedir a análise de um dado cadastral da minha conta.")).toBeVisible();

  await page.reload();
  await expect(page.getByText("PRV-20261026-TEST000002")).toBeVisible();
  await expect(page.getByText("Recebida", { exact: true }).first()).toBeVisible();
  await expect(page.locator("input[type=file], button:has-text('Anexar')")).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("privacy-student.png"), fullPage: true });
});

test("admin inicia revisão e encerra com uma resposta registrada", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  const request: Omit<typeof initialStudentRequest, "status" | "responded_at" | "response_summary" | "rejection_reason"> & {
    user: { id: string; name: string; matricula: string };
    status: string;
    responded_at: string | null;
    response_summary: string | null;
    rejection_reason: string | null;
  } = {
    ...initialStudentRequest,
    user: { id: "student-privacy", name: "Aluna QA", matricula: "QA0001" },
  };
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (failed) => failures.push(failed.method() + " " + failed.url()));
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "privacy-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ id: "admin-privacy", name: "Admin QA", role: "admin" }),
  }));
  await page.route("**/api/notifications**", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }),
  }));
  await page.route("**/api/admin/privacy/data-requests**", async (route) => {
    const apiRequest = route.request();
    if (apiRequest.method() === "PATCH") {
      const input = apiRequest.postDataJSON() as { status: string; response_summary?: string; rejection_reason?: string };
      Object.assign(request, input);
      if (input.status === "completed" || input.status === "rejected") request.responded_at = "2026-10-26T13:00:00.000Z";
      return route.fulfill({ contentType: "application/json", body: JSON.stringify(request) });
    }
    const url = new URL(apiRequest.url());
    const filtered = !url.searchParams.get("status") || request.status === url.searchParams.get("status") ? [request] : [];
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: filtered, current_page: 1, last_page: 1, total: filtered.length }) });
  });

  await page.goto("/dashboard/admin/privacy-requests");
  await expect(page.getByRole("heading", { name: "Solicitações sobre dados pessoais" })).toBeVisible();
  await page.getByRole("button", { name: /Aluna QA/ }).click();
  await page.getByRole("button", { name: "Registrar etapa" }).click();
  await expect(page.getByText("Em análise humana", { exact: true }).first()).toBeVisible();
  await page.getByLabel("Próxima etapa").selectOption("completed");
  await page.getByLabel("Resumo privado da resposta ao solicitante").fill("A solicitação foi analisada e respondida pela equipe.");
  await page.getByRole("button", { name: "Registrar etapa" }).click();
  await expect(page.getByText("Etapa atualizada e registrada no histórico de atendimento.", { exact: true })).toBeVisible();
  await expect(page.getByText("A solicitação foi analisada e respondida pela equipe.")).toBeVisible();
  await expect(page.getByText("Respondida", { exact: true }).last()).toBeVisible();
  await expect(page.getByText("Esta solicitação está encerrada e permanece disponível para consulta.")).toBeVisible();
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("privacy-admin.png"), fullPage: true });
});
