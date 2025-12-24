"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";

export default function NewRequestPage() {
  const router = useRouter();
  
  // 1. Estados para capturar os dados do formulário
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [typeId, setTypeId] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [requestTypes, setRequestTypes] = useState<any[]>([]);

  // 2. Carregar os tipos de requerimento do banco
  useEffect(() => {
    const fetchTypes = async () => {
      const token = localStorage.getItem("token");
      try {
        // 👇 CORREÇÃO 1: Usando 127.0.0.1
        const res = await fetch("http://127.0.0.1:8000/api/type-requests", {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (res.ok) {
          const data = await res.json();
          setRequestTypes(data);
        }
      } catch (err) {
        console.error("Erro ao carregar tipos:", err);
      }
    };
    fetchTypes();
  }, []);

  // 3. Função de Envio Corrigida
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file || !typeId) return alert("Por favor, preencha todos os campos.");

    setLoading(true);
    const token = localStorage.getItem("token");

    try {
      // PASSO 1: Upload do Arquivo
      const formData = new FormData();
      formData.append("arquivo", file); 

      // 👇 CORREÇÃO 2: Usando 127.0.0.1
      const uploadRes = await fetch("http://127.0.0.1:8000/api/documents/upload", {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
      });

      const uploadData = await uploadRes.json();
      if (!uploadRes.ok) throw new Error(uploadData.msg || "Falha no upload do arquivo.");

      // PASSO 2: Criação do Requerimento
      // 👇 CORREÇÃO 3: Usando 127.0.0.1
      const requestRes = await fetch("http://127.0.0.1:8000/api/requests", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          type_id: typeId,          
          subject: subject,         
          description: description, 
          document_ids: [uploadData.id], 
        }),
      });

      const requestData = await requestRes.json();

      if (requestRes.ok) {
        alert("Requerimento enviado com sucesso!");
        router.push("/dashboard");
      } else {
        const msg = requestData.msg || requestData.message || "Erro ao criar requerimento.";
        throw new Error(msg);
      }

    } catch (err: any) {
      console.error(err);
      alert(err.message); 
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-8 bg-white shadow rounded-lg mt-10">
      <h1 className="text-2xl font-bold mb-6 text-gray-800">Novo Requerimento</h1>
      
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Seleção do Tipo */}
        <div>
          <label className="block text-sm font-medium text-gray-700">Tipo de Requerimento</label>
          <select 
            required
            className="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
            value={typeId}
            onChange={(e) => setTypeId(e.target.value)}
          >
            <option value="">Selecione o tipo de solicitação</option>
            {requestTypes.map((t) => (
              <option key={t.id} value={t.id}>{t.name}</option>
            ))}
          </select>
        </div>

        {/* Assunto */}
        <div>
          <label className="block text-sm font-medium text-gray-700">Assunto</label>
          <input 
            required
            type="text" 
            className="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            placeholder="Ex: Requerimento de Segunda Chamada"
          />
        </div>

        {/* Descrição */}
        <div>
          <label className="block text-sm font-medium text-gray-700">Descrição/Justificativa</label>
          <textarea 
            required
            className="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm h-32 focus:ring-green-500 focus:border-green-500"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
          />
        </div>

        {/* Upload de Arquivo */}
        <div>
          <label className="block text-sm font-medium text-gray-700">Anexo (PDF ou Imagem)</label>
          <input 
            required
            type="file" 
            accept=".pdf,.png,.jpg,.jpeg"
            onChange={(e) => setFile(e.target.files ? e.target.files[0] : null)}
            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
          />
        </div>

        <button 
          type="submit" 
          disabled={loading}
          className="w-full bg-green-600 text-white font-bold py-3 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"
        >
          {loading ? "Processando..." : "Enviar Requerimento"}
        </button>
      </form>
    </div>
  );
}