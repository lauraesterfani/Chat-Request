"use client";

import { useState, useEffect } from "react";
import Link from "next/link";

const API_BASE = "http://127.0.0.1:8000/api";

export default function StudentDashboard() {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchMyRequests = async () => {
      const token = localStorage.getItem("jwt_token");
      try {
        const res = await fetch(`${API_BASE}/requests`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (res.ok) {
          const data = await res.json();
          setRequests(data);
        }
      } catch (error) {
        console.error("Erro ao buscar requerimentos");
      } finally {
        setLoading(false);
      }
    };
    fetchMyRequests();
  }, []);

  return (
    <div className="p-8 max-w-5xl mx-auto">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-800">Meus Requerimentos</h1>
        <Link 
          href="/requests/new" 
          className="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition"
        >
          + Novo Pedido
        </Link>
      </div>

      {loading ? (
        <p>Carregando...</p>
      ) : requests.length === 0 ? (
        <div className="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-20 text-center">
          <p className="text-gray-500">Você ainda não possui requerimentos abertos.</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {requests.map((req: any) => (
            <div key={req.id} className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
              <div>
                <p className="text-sm text-gray-400 font-mono">#{req.protocol}</p>
                <h3 className="text-lg font-bold text-gray-700">{req.subject}</h3>
                <p className="text-sm text-gray-500">{new Date(req.created_at).toLocaleDateString('pt-BR')}</p>
              </div>
              <div className="text-right">
                <span className={`px-4 py-1 rounded-full text-xs font-bold uppercase ${
                  req.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                  req.status === 'analyzing' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
                }`}>
                  {req.status === 'pending' ? 'Aberto' : req.status === 'analyzing' ? 'Em Análise' : 'Concluído'}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}