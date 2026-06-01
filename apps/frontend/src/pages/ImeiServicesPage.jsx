import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/imei/services')).data

export default function ImeiServicesPage() {
  const { data, isLoading } = useQuery({ queryKey: ['imei-services'], queryFn: fetchServices })

  if (isLoading) return <p>Loading IMEI services...</p>

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">IMEI Services</h2>
      <div className="grid gap-3 md:grid-cols-2">
        {data?.map((category) => (
          <section key={category.id} className="rounded border border-slate-800 bg-slate-900 p-4">
            <h3 className="text-lg font-medium">{category.name}</h3>
            <p className="mb-3 text-sm text-slate-400">{category.description}</p>
            <div className="space-y-2 text-sm">
              {category.services?.map((service) => (
                <div key={service.id} className="rounded border border-slate-800 px-3 py-2">
                  <p className="font-medium">{service.name}</p>
                  <p>Price: ${service.price}</p>
                  <p>ETA: {service.estimated_time_minutes ?? '-'} min</p>
                </div>
              ))}
            </div>
          </section>
        ))}
      </div>
    </div>
  )
}
