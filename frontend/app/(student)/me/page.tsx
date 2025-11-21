'use client';

import { useRouter } from 'next/navigation';

export default function MePage() {
  const router = useRouter();

  return (
    <div className="flex flex-col items-center justify-start mt-10 gap-10">

      <div className="flex gap-10">

        {/* Botão Solicitar Requerimento */}
        <button
          onClick={() => router.push('/chat')}
          className="bg-white shadow-md px-10 py-10 text-lg rounded-xl hover:shadow-lg transition"
        >
          Solicitar Requerimento
        </button>

        {/* Botão Consultar Requerimento */}
        <button
          onClick={() => router.push('/requests')}
          className="bg-white shadow-md px-10 py-10 text-lg rounded-xl hover:shadow-lg transition"
        >
          Consultar Requerimento
        </button>

      </div>
    </div>
  );
}
