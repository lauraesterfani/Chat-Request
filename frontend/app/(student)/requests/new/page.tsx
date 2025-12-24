"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter, useSearchParams } from "next/navigation";

const API_BASE = "http://127.0.0.1:8000/api";

export default function NewRequestPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const autoSelectTerm = searchParams.get('q'); 

  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [typeId, setTypeId] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [requestTypes, setRequestTypes] = useState<any[]>([]);
  const [errorMsg, setErrorMsg] = useState("");

  // Lógica de Identificação de Anexo (Mantida igual)
  const requiresFile = useMemo(() => {
    const selectedType = requestTypes.find(t => t.id === typeId);
    if (!selectedType) return false;
    const name = selectedType.name.toLowerCase();
    const typesWithAnexo = [
      "aproveitamento", "isenção", "justificativa", "falta", 
      "chamada", "educação física", "transferência", "cancelamento"
    ];
    return typesWithAnexo.some(keyword => name.includes(keyword));
  }, [typeId, requestTypes]);

  // Fetch de Tipos
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

  // Seleção Automática via URL
  useEffect(() => {
    if (autoSelectTerm && requestTypes.length > 0) {
      const found = requestTypes.find(t => 
        t.name.toLowerCase().includes(autoSelectTerm.toLowerCase())
      );
      if (found) setTypeId(found.id);
    }
  }, [autoSelectTerm, requestTypes]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");

    if (requiresFile && !file) return alert("Este requerimento exige um anexo!");

    setLoading(true);
    const token = localStorage.getItem("jwt_token");

    try {
      let documentIds: string[] = [];

      if (requiresFile && file) {
        const formData = new FormData();
        formData.append("arquivo", file);
        
        const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
          body: formData,
        });

        const uploadData = await uploadRes.json();
        if (!uploadRes.ok) throw new Error(uploadData.msg || "Falha no upload.");
        documentIds = [uploadData.id];
      }

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
          document_ids: documentIds
        }),
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
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
      
      {/* Botão Voltar */}
      <div className="max-w-2xl mx-auto mb-6">
        <button 
          onClick={() => router.back()} 
          className="flex items-center text-gray-500 hover:text-green-600 transition-colors text-sm font-medium"
        >
          <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Voltar para Dashboard
        </button>
      </div>

      <div className="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        {/* Cabeçalho do Card */}
        <div className="bg-gradient-to-r from-green-600 to-green-500 px-8 py-6">
          <h1 className="text-2xl font-bold text-white flex items-center gap-2">
            <svg className="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Novo Requerimento
          </h1>
          <p className="text-green-100 text-sm mt-1 opacity-90">Preencha os dados abaixo para abrir uma solicitação na secretaria.</p>
        </div>

        <div className="p-8">
          {errorMsg && (
            <div className="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
              <div className="flex">
                <div className="flex-shrink-0">
                  <svg className="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd"/></svg>
                </div>
                <div className="ml-3">
                  <p className="text-sm text-red-700 font-medium">{errorMsg}</p>
                </div>
              </div>
            </div>
          )}
          
          <form onSubmit={handleSubmit} className="space-y-6">
            
            {/* Campo Tipo */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Tipo de Solicitação</label>
              <div className="relative">
                <select 
                  className="block w-full pl-3 pr-10 py-3 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-lg border shadow-sm transition-all"
                  value={typeId}
                  onChange={(e) => setTypeId(e.target.value)}
                  required
                >
                  <option value="">Selecione uma opção...</option>
                  {requestTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </div>
            </div>

            {/* Campo Assunto */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Assunto</label>
               <input 
                 type="text" 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all sm:text-sm" 
                 placeholder="Ex: Correção de nota na disciplina X"
                 value={subject} 
                 onChange={(e) => setSubject(e.target.value)} 
                 required 
               />
            </div>

            {/* Campo Descrição */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Descrição Detalhada</label>
               <textarea 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all sm:text-sm h-32 resize-none" 
                 placeholder="Descreva sua solicitação com detalhes..."
                 value={description} 
                 onChange={(e) => setDescription(e.target.value)} 
                 required 
               />
            </div>

            {/* Área de Upload Condicional */}
            <div className="pt-2">
              {requiresFile ? (
                <div className="rounded-xl border-2 border-dashed border-yellow-400 bg-yellow-50 p-6 transition-all hover:bg-yellow-100 group">
                   <div className="flex flex-col items-center justify-center text-center">
                      <div className="p-3 bg-yellow-100 rounded-full mb-3 group-hover:bg-yellow-200 transition-colors">
                        <svg className="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                      </div>
                      <label className="block text-sm font-bold text-gray-800 mb-1 cursor-pointer">
                        Anexo Obrigatório
                        <span className="font-normal text-gray-600"> (PDF ou Imagem)</span>
                      </label>
                      <input 
                        type="file" 
                        onChange={(e) => setFile(e.target.files?.[0] || null)} 
                        className="mt-2 block w-full text-sm text-slate-500
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-full file:border-0
                          file:text-sm file:font-semibold
                          file:bg-yellow-600 file:text-white
                          hover:file:bg-yellow-700
                          cursor-pointer"
                        accept=".pdf,.jpg,.png"
                        required
                      />
                      <p className="text-xs text-yellow-700 mt-3 bg-yellow-200/50 px-3 py-1 rounded-full font-medium">
                        ⚠️ Documentação exigida para este tipo de pedido.
                      </p>
                   </div>
                </div>
              ) : (
                <div className="rounded-xl bg-blue-50 p-4 border border-blue-100 flex items-start gap-3">
                  <div className="flex-shrink-0 mt-0.5">
                    <svg className="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/></svg>
                  </div>
                  <p className="text-sm text-blue-700">
                    Este tipo de solicitação <strong>não exige anexos</strong>. Pode enviar diretamente.
                  </p>
                </div>
              )}
            </div>

            {/* Botão de Enviar */}
            <button 
              type="submit" 
              disabled={loading} 
              className={`w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white transition-all transform hover:-translate-y-0.5
                ${loading 
                  ? "bg-gray-400 cursor-not-allowed" 
                  : "bg-green-600 hover:bg-green-700 hover:shadow-green-200"
                }`}
            >
              {loading ? (
                <span className="flex items-center gap-2">
                  <svg className="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Processando envio...
                </span>
              ) : "Enviar Requerimento"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}