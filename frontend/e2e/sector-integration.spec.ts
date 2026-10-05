import { expect, test } from "@playwright/test";
import { readFileSync } from "node:fs";
import path from "node:path";

test.describe("fluxo setorial integrado", () => {
  test.skip(!process.env.CHAT_REQUEST_INTEGRATION_QA, "Requer API Laravel e banco de teste isolado com seeders de desenvolvimento.");
  test.setTimeout(60_000);

  test("coordenação abre anexo privado e utiliza resposta do próprio setor", async ({ page, request }) => {
    const errors: string[] = [];
    const failedRequests: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    page.on("requestfailed", (failed) => {
      if (failed.url().includes("/api/")) failedRequests.push(`${failed.method()} ${failed.url()}`);
    });

    const password = process.env.CHAT_REQUEST_QA_PASSWORD;
    expect(password, "Defina CHAT_REQUEST_QA_PASSWORD somente no ambiente de teste isolado").toBeTruthy();

    const studentLogin = await request.post("/api/login", { data: { matricula: "20241TSIIG001", password } });
    expect(studentLogin.ok()).toBeTruthy();
    const studentToken = (await studentLogin.json()).token as string;
    const cradtLogin = await request.post("/api/login/staff", { data: { email: "qa.cradt@example.test", password } });
    expect(cradtLogin.ok()).toBeTruthy();
    const cradtToken = (await cradtLogin.json()).token as string;

    const typesResponse = await request.get("/api/type-requests");
    const types = await typesResponse.json() as Array<{ id: string; name: string; requires_document: boolean }>;
    const type = types.find((item) => !item.requires_document);
    expect(type).toBeDefined();

    const image = readFileSync(path.join(process.cwd(), "public", "mascote.png"));
    const upload = await request.post("/api/documents/upload", {
      headers: { Authorization: `Bearer ${studentToken}` },
      multipart: { arquivo: { name: "mascote.png", mimeType: "image/png", buffer: image } },
    });
    expect(upload.status()).toBe(201);
    const documentId = (await upload.json()).id as string;

    const creation = await request.post("/api/requests", {
      headers: { Authorization: `Bearer ${studentToken}` },
      data: { type_id: type!.id, subject: "Validação visual de anexo", description: "Pedido fictício criado pelo teste de integração.", document_ids: [documentId] },
    });
    expect(creation.status()).toBe(201);
    const requestId = (await creation.json()).id as string;

    const forwarding = await request.post(`/api/requests/${requestId}/forward`, {
      headers: { Authorization: `Bearer ${cradtToken}` },
      data: { sector: "COORDENACAO", reason: "Validação do acesso do setor atual" },
    });
    expect(forwarding.ok()).toBeTruthy();

    await page.goto("/cradt-login");
    await page.locator('input[type="email"]').fill("qa.coordenacao@example.test");
    await page.locator('input[type="password"]').fill(password!);
    await page.getByRole("button", { name: "Acessar Painel" }).click();
    await expect(page).toHaveURL(/dashboard\/coordenacao/);
    await expect(page.getByRole("heading", { name: "Requerimentos da Coordenação" })).toBeVisible();
    await page.locator(`a[href="/requests/visualizar/${requestId}"]`).click();
    const coordinationPreview = page.getByRole("img", { name: /mascote.png/ });
    await expect.poll(async () => coordinationPreview.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBeTruthy();

    await page.goto("/dashboard/admin/response-templates");
    await expect(page.getByRole("heading", { name: "Respostas pré-configuradas" })).toBeVisible();
    await page.getByRole("button", { name: "Nova resposta" }).click();
    const dialog = page.getByRole("dialog", { name: "Nova resposta" });
    await dialog.getByRole("textbox", { name: "Título" }).fill(`Coordenação QA ${requestId.slice(0, 8)}`);
    await dialog.getByRole("textbox", { name: "Conteúdo da mensagem" }).fill("Orientação de teste da coordenação.");
    await expect(dialog.getByRole("combobox", { name: "Setor" })).toHaveValue("COORDENACAO");
    await dialog.getByRole("checkbox", { name: type!.name }).check();
    await dialog.getByRole("button", { name: "Salvar resposta" }).click();
    await expect(page.getByText(`Coordenação QA ${requestId.slice(0, 8)}`)).toBeVisible();

    await page.goto(`/requests/acesso/${requestId}`);
    await expect(page.getByRole("heading", { name: "Documentos Anexados" })).toBeVisible();
    const preview = page.getByRole("img", { name: /mascote.png/ });
    await expect(preview).toBeVisible();
    await expect.poll(async () => preview.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBeTruthy();
    await expect(page.getByRole("option", { name: new RegExp(`Coordenação QA ${requestId.slice(0, 8)}`) }).first()).toBeAttached();
    expect(errors, errors.join("\n")).toEqual([]);
    expect(failedRequests, failedRequests.join("\n")).toEqual([]);
  });
});
