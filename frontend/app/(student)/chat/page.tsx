"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";

export default function ChatPage() {
  const router = useRouter();
  const [requestTypes, setRequestTypes] = useState([]);

  useEffect(() => {
    fetch("http://127.0.0.1:8000/api/type-requests")
      .then(res => res.json())
      .then(data => setRequestTypes(data));
  }, []);

  return (
    <div className="max-w-2xl mx-auto p-6 flex flex-col items-center">
      <img src="/mascote.png" alt="Mascote" className="h-32 mb-6" />
      <h2 className="text-xl font-bold mb-6 text-gray-800 text-center">O que você deseja solicitar hoje?</h2>
      
      <div className="w-full space-y-3">
        {requestTypes.map((type: any) => (
          <button
            key={type.id}
            onClick={() => router.push(`/requests/new?type_id=${type.id}`)}
            className="w-full p-4 bg-white border border-gray-100 rounded-2xl text-left font-bold text-gray-700 shadow-sm hover:bg-green-50 hover:border-green-200 transition-all"
          >
            📝 {type.name}
          </button>
        ))}
      </div>
    </div>
  );
}