"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter } from "next/navigation";

const API_BASE = "http://127.0.0.1:8000/api";

export default function NewRequestPage() {
  const router = useRouter();

  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [typeId, setTypeId] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [requestTypes, setRequestTypes] = useState<any[]>([]);
  const [errorMsg, setErrorMsg] = useState("");

  // 1. Identifica se o tipo selecionado exige upload (Baseado no PDF do IFPE)
  const requiresFile = useMemo(() => {
    const selectedType = requestTypes.find(t => t.id === typeId);
    if (!selectedType) return false;

    const name = selectedType.name.toLowerCase();
    // Lista de palavras-chave que exigem anexo conforme o documento [cite: 49, 56, 57]
    const typesWithAnexo = [
      "aproveitamento", "isenção", "justificativa", "falta", 
      "chamada", "educação física", "transferência", "cancelamento"
    ];

    return typesWithAnexo.some(keyword => name.includes(keyword));
  }, [typeId, requestTypes]);

  useEffect(() => {
    const fetchTypes = async () => {
      const token = localStorage.getItem("jwt_token");
      if (!token) return;

      try {
        const res = await fetch(`${API_BASE}/type-requests`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (res.ok) setRequestTypes(await res.json());
      } catch (error) {
        setErrorMsg("Erro de conexão com o servidor.");
      }
    };
    fetchTypes();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");

    // Validação dinâmica: só exige arquivo se requiresFile for true
    if (requiresFile && !file) return alert("Este requerimento exige um anexo!");

    setLoading(true);
    const token = localStorage.getItem("jwt_token");

    try {
      let documentIds: string[] = [];

      // PASSO A: Upload apenas se necessário
      if (requiresFile && file) {
        const formData = new FormData();
        formData.append("arquivo", file);
        
        const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
          body: formData,
          credentials: 'omit',
        });

        const uploadData = await uploadRes.json();
        if (!uploadRes.ok) throw new Error(uploadData.msg || "Falha no upload.");
        documentIds = [uploadData.id];
      }

      // PASSO B: Criar Requerimento
      const reqRes = await fetch(`${API_BASE}/requests`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          type_id: typeId,
          subject,
          description,
          document_ids: documentIds // Pode ir vazio se não houver anexo
        }),
        credentials: 'omit',
      });

      if (reqRes.ok) {
        alert("Requerimento enviado com sucesso! 🚀");
        router.push("/dashboard");
      } else {
        const reqData = await reqRes.json();
        throw new Error(reqData.msg || "Erro ao salvar requerimento.");
      }

    } catch (err: any) {
      setErrorMsg(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-8 mt-10 bg-white shadow-lg rounded-xl">
      <h1 className="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Novo Requerimento</h1>

      {errorMsg && (
        <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
          <p>{errorMsg}</p>
        </div>
      )}
      
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-bold text-gray-700 mb-2">Tipo de Solicitação</label>
          <select 
            className="w-full border p-3 rounded bg-white"
            value={typeId}
            onChange={(e) => setTypeId(e.target.value)}
            required
          >
            <option value="">Selecione uma opção...</option>
            {requestTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </select>
        </div>

        <div>
           <label className="block text-sm font-medium text-gray-700">Assunto</label>
           <input type="text" className="w-full border p-3 rounded" value={subject} onChange={(e) => setSubject(e.target.value)} required />
        </div>

        <div>
           <label className="block text-sm font-medium text-gray-700">Descrição</label>
           <textarea className="w-full border p-3 rounded h-32" value={description} onChange={(e) => setDescription(e.target.value)} required />
        </div>

        {/* === RENDERIZAÇÃO CONDICIONAL DO UPLOAD === */}
        {requiresFile ? (
          <div className="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
             <label className="block text-sm font-bold text-yellow-800 mb-2">Anexo Obrigatório (PDF ou Imagem)</label>
             <input 
               type="file" 
               onChange={(e) => setFile(e.target.files?.[0] || null)} 
               className="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-yellow-100 file:text-yellow-700"
               accept=".pdf,.jpg,.png"
               required
             />
             <p className="text-xs text-yellow-600 mt-1">* Este tipo de pedido exige documentação comprobatória.</p>
          </div>
        ) : (
          <div className="p-4 bg-blue-50 rounded text-blue-700 text-sm">
            ℹ️ Este requerimento não exige anexos de documentos.
          </div>
        )}

        <button type="submit" disabled={loading} className="w-full bg-green-600 text-white font-bold py-4 rounded-lg hover:bg-green-700 transition">
          {loading ? "Processando..." : "Enviar Requerimento"}
        </button>
      </form>
    </div>
  );
}