"use client";
import { useState, useEffect } from "react";
import axios from "axios";
import { Filter, GraduationCap, User, FileText } from "lucide-react";

interface CradtFiltersProps {
  onFilter: (params: any) => void;
}

export default function CradtFilters({ onFilter }: CradtFiltersProps) {
  const [courses, setCourses] = useState<any[]>([]);
  const [filters, setFilters] = useState({ nome: "", matricula: "", course_id: "" });

  useEffect(() => {
    // Carrega os cursos para o select
    axios.get("http://127.0.0.1:8000/api/courses")
      .then(res => setCourses(res.data.data || res.data))
      .catch(err => console.error(err));
  }, []);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    const newFilters = { ...filters, [name]: value };
    setFilters(newFilters);
    onFilter(newFilters);
  };

  return (
    <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-8 space-y-4">
      <div className="flex items-center gap-2 mb-2">
        <Filter size={18} className="text-[#15803d]" />
        <h3 className="text-xs font-bold text-slate-500 uppercase">Filtros de Busca</h3>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Nome */}
        <div className="relative group">
          <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><User size={18} /></div>
          <input name="nome" type="text" placeholder="Nome do aluno..." onChange={handleChange}
            className="w-full pl-12 pr-4 py-3.5 bg-[#f8fafc] border border-gray-100 rounded-2xl text-sm outline-none focus:border-[#15803d]" />
        </div>
        {/* Matrícula */}
        <div className="relative group">
          <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><FileText size={18} /></div>
          <input name="matricula" type="text" placeholder="Nº de matrícula..." onChange={handleChange}
            className="w-full pl-12 pr-4 py-3.5 bg-[#f8fafc] border border-gray-100 rounded-2xl text-sm outline-none focus:border-[#15803d]" />
        </div>
        {/* Curso */}
        <div className="relative group">
          <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><GraduationCap size={18} /></div>
          <select name="course_id" onChange={handleChange}
            className="w-full pl-12 pr-4 py-3.5 bg-[#f8fafc] border border-gray-100 rounded-2xl text-sm outline-none focus:border-[#15803d] appearance-none cursor-pointer">
            <option value="">Todos os cursos</option>
            {courses.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
          </select>
        </div>
      </div>
    </div>
  );
}