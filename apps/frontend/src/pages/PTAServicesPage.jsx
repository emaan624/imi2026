import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/pta/services')).data

export default function PTAServicesPage() {
  const { data: services = [] } = useQuery({ queryKey: ['pta-services'], queryFn: fetchServices })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">PTA Services</h2>
      <div className="grid gap-3 md:grid-cols-2">
        {services.map((service) => (
          <section key={service.id} className="rounded border border-slate-800 bg-slate-900 p-4">
            <h3 className="font-medium">{service.name}</h3>
            <p className="text-sm text-slate-400">{service.description || 'PTA service offering'}</p>
            <p className="mt-2 text-cyan-300">Service Fee: {service.base_fee}</p>
          </section>
        ))}
      </div>
    </div>
  )
}
