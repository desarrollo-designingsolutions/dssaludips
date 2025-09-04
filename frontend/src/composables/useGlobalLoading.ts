import { computed, ref } from "vue"

declare const echo: any

interface ImportProcess {
  batch_id: string
  progress: number
  current_element: string
  current_action: string
  status: "active" | "completed" | "error" | "queued" | "failed"
  started_at?: string
  completed_at?: string
  websocket_channel?: any
  metadata?: {
    total_records?: number
    processed_records?: number
    processed_records_in_chunk?: number
    current_sheet?: number
    total_sheets?: number
    errors_count?: number 
    processing_speed?: number
    estimated_time_remaining?: number
    file_size?: number
    file_name: string,
    connection_status?: "connected" | "connecting" | "reconnecting" | "error" | "disconnected"
    last_activity?: string
    memory_usage?: number
    cpu_usage?: number
    processing_start_time?: string
  }
}

// Variables reactivas globales (SINGLETON)
const allProcesses = ref<ImportProcess[]>([])
const isMinimized = ref(false)
const showProcessList = ref(false)

// Callbacks para eventos externos
const callbacks = {
  completed: [] as Array<(batchId: string) => void>,
  error: [] as Array<(batchId: string, error: any) => void>,
  progressUpdated: [] as Array<(batchId: string, progress: number) => void>,
}

const getEcho = () => {
  if (typeof echo !== "undefined") {
    return echo
  }
  if (typeof window !== "undefined" && (window as any).Echo) {
    return (window as any).Echo
  }
  console.error("❌ Echo no está disponible")
  return null
}

const startWebSocket = (process: ImportProcess) => {
  console.log(`🔌 [WEBSOCKET] Iniciando para batch: ${process.batch_id}`)
  const echoInstance = getEcho()
  if (!echoInstance) {
    console.error(`❌ [WEBSOCKET] Echo no disponible`)
    return
  }
  try {
    const channelName = `import.progress.${process.batch_id}`
    console.log(`🔌 [WEBSOCKET] Creando canal: ${channelName}`)
    const channel = echoInstance.channel(channelName)
    process.websocket_channel = channel

    if (process.metadata) {
      process.metadata.connection_status = "connecting"
    }

    channel.listen(".progress.update", (data: any) => {
      console.log(`🔥 [WEBSOCKET] EVENTO RECIBIDO para ${process.batch_id}:`, data)
      handleProgressUpdate(process.batch_id, data)
    })

    channel.subscribed(() => {
      console.log(`✅ [WEBSOCKET] Conectado al canal ${channelName}`)
      if (process.metadata) {
        process.metadata.connection_status = "connected"
        process.metadata.last_activity = new Date().toISOString()
      }
    })

    channel.error((error: any) => {
      console.error(`❌ [WEBSOCKET] Error en canal ${channelName}:`, error)
      if (process.metadata) {
        process.metadata.connection_status = "error"
      }
      callbacks.error.forEach((callback) => callback(process.batch_id, error))
    })
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Error creando canal para ${process.batch_id}:`, error)
  }
}

const stopWebSocket = (process: ImportProcess) => {
  if (!process || !process.websocket_channel) return
  console.log(`🛑 [WEBSOCKET] Deteniendo para ${process.batch_id}`)
  try {
    const echoInstance = getEcho()
    if (echoInstance) {
      echoInstance.leaveChannel(`import.progress.${process.batch_id}`)
    }
    process.websocket_channel = undefined
    if (process.metadata) {
      process.metadata.connection_status = "disconnected"
    }
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Error deteniendo:`, error)
  }
}

const handleProgressUpdate = (batchId: string, data: any) => {
  const process = allProcesses.value.find((p) => p.batch_id === batchId)
  if (!process) {
    console.warn(`⚠️ [UPDATE] Proceso no encontrado para batchId: ${batchId}`)
    return
  }

  console.log(`📊 [UPDATE] Datos recibidos para ${batchId}:`, data)
  console.log(`🔍 [UPDATE] Metadata del backend:`, data.metadata)

  let progress = 0
  if (data.metadata?.general_progress !== undefined) {
    progress = data.metadata.general_progress
  } else if (data.progress !== undefined) {
    progress = data.progress
  }
  process.progress = Math.min(100, Math.max(0, progress))

  process.current_element = data.current_element || "Procesando..."
  process.current_action = data.current_action || "Importando datos"
  process.status = data.status

  if (!process.metadata) {
    process.metadata = {}
  }
  if (data.metadata) {
    process.metadata = {
      ...process.metadata,
      total_records: data.metadata.total_records || process.metadata.total_records || 0,
      processed_records: data.metadata.processed_records || process.metadata.processed_records || 0,
      processed_records_in_chunk:
        data.metadata.processed_records_in_chunk || process.metadata.processed_records_in_chunk || 0,
      current_sheet: data.metadata.current_sheet || process.metadata.current_sheet || 1,
      total_sheets: data.metadata.total_sheets || process.metadata.total_sheets || 1,
      errors_count: data.metadata.errors_count || process.metadata.errors_count || 0, 
      file_size: data.metadata.file_size || process.metadata.file_size || 0,
      file_name: data.metadata.file_name || process.metadata.file_name || 0,
      processing_start_time: data.metadata.processing_start_time || process.metadata.processing_start_time,
      memory_usage: data.metadata.memory_usage || process.metadata.memory_usage || 0,
      cpu_usage: data.metadata.cpu_usage || process.metadata.cpu_usage || 0,
      connection_status: "connected",
      last_activity: new Date().toISOString(),
      processing_speed: data.metadata.processing_speed || 0,
      estimated_time_remaining: data.metadata.estimated_time_remaining || 0,
    }
  }

  console.log(`✅ [UPDATE] Progreso actualizado a ${process.progress}% para ${batchId}`)
  callbacks.progressUpdated.forEach((callback) => callback(batchId, process.progress))

  if (process.status === "completed" || process.status === "error") {
    console.log(`🎉 [UPDATE] ¡COMPLETADO/ERROR! ${batchId}`)
    process.status = data.status
    process.completed_at = new Date().toISOString()
    stopWebSocket(process)
    setTimeout(() => {
      callbacks.completed.forEach((callback) => callback(batchId))
    }, 500)
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return "0 Bytes"
  const k = 1024
  const sizes = ["Bytes", "KB", "MB", "GB"]
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
}

const formatTime = (seconds: number): string => {
  if (seconds < 60) return `${seconds}s`
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}m ${remainingSeconds}s`
}

export function useGlobalLoading() {
  const activeProcesses = computed(() => {
    return allProcesses.value.filter((p) => p.status === "active")
  })

  const queuedProcesses = computed(() => {
    return allProcesses.value.filter((p) => p.status === "queued")
  })

  const completedProcesses = computed(() => {
    return allProcesses.value.filter((p) => p.status === "completed" || p.status === "error" || p.status === "failed")
  })

  const sortedProcesses = computed(() => {
    const active = activeProcesses.value.sort((a, b) => {
      const dateA = new Date(a.started_at || "").getTime()
      const dateB = new Date(b.started_at || "").getTime()
      return dateA - dateB
    })
    const queued = queuedProcesses.value.sort((a, b) => {
      const dateA = new Date(a.started_at || "").getTime()
      const dateB = new Date(b.started_at || "").getTime()
      return dateA - dateB
    })
    const completed = completedProcesses.value.sort((a, b) => {
      const dateA = new Date(a.completed_at || a.started_at || "").getTime()
      const dateB = new Date(b.completed_at || b.started_at || "").getTime()
      return dateB - dateA
    })
    return [...active, ...queued, ...completed]
  })

  const currentProcess = computed<ImportProcess | null>(() => {
    if (activeProcesses.value.length > 0) {
      return activeProcesses.value[0]
    }
    if (queuedProcesses.value.length > 0) {
      return queuedProcesses.value[0]
    }
    if (completedProcesses.value.length > 0) {
      return completedProcesses.value[0]
    }
    return null
  })

  const isLoading = computed(() => {
    const result = allProcesses.value.some((p) => p.status !== "completed" && p.status !== "error")
    console.log(
      `DEBUG: isLoading computed re-evaluated. allProcesses.length: ${allProcesses.value.length}, result: ${result}`,
    )
    return result
  })

  const hasMultipleProcesses = computed(() => {
    return allProcesses.value.length > 1
  })

  const currentProgress = computed(() => {
    return currentProcess.value?.progress || 0
  })

  const debugInfo = computed(() => {
    const process = currentProcess.value
    if (!process) return {}
    return {
      batchId: process.batch_id,
      progress: process.progress,
      status: process.status,
      currentElement: process.current_element,
      currentAction: process.current_action,
      queueLength: queuedProcesses.value.length,
      totalProcesses: allProcesses.value.length,
      total_records: process.metadata?.total_records || 0,
      processed_records: process.metadata?.processed_records || 0,
      processed_records_in_chunk: process.metadata?.processed_records_in_chunk || 0,
      current_sheet: process.metadata?.current_sheet || 1,
      total_sheets: process.metadata?.total_sheets || 1,
      errors_count: process.metadata?.errors_count || 0, 
      connection_status: process.metadata?.connection_status || "disconnected",
      processing_speed: process.metadata?.processing_speed || 0,
      estimated_time_remaining: process.metadata?.estimated_time_remaining || 0,
      memory_usage: process.metadata?.memory_usage || 0,
      cpu_usage: process.metadata?.cpu_usage || 0,
      file_size: process.metadata?.file_size || 0,
      last_activity: process.metadata?.last_activity || "N/A",
      processing_start_time: process.metadata?.processing_start_time || "N/A",
      fileSize: process.metadata?.file_size ? formatFileSize(process.metadata.file_size) : "N/A",
      processingSpeedFormatted: process.metadata?.processing_speed
        ? `${process.metadata.processing_speed} reg/s`
        : "N/A",
      estimatedTimeFormatted: process.metadata?.estimated_time_remaining
        ? formatTime(process.metadata.estimated_time_remaining)
        : "N/A",
    }
  })

  const startLoading = (batchId: string, fileName?: string) => {
    console.log(`🚀 [START] Iniciando carga para batch: ${batchId}`)
    const file_name = fileName || "Archivo"

    const newProcess: ImportProcess = {
      batch_id: batchId,
      progress: 0,
      current_element: "Iniciando proceso...",
      current_action: "Preparando importación",
      status: "active",
      started_at: new Date().toISOString(),
      metadata: {
        total_records: 0,
        processed_records: 0,
        processed_records_in_chunk: 0,
        current_sheet: 1,
        total_sheets: 1,
        errors_count: 0, 
        processing_speed: 0,
        estimated_time_remaining: 0,
        file_size: 0,
        file_name: file_name,
        connection_status: "connecting",
        last_activity: new Date().toISOString(),
        processing_start_time: new Date().toISOString(),
        memory_usage: 0,
        cpu_usage: 0,
      },
    }
    allProcesses.value.push(newProcess)

    // *** NUEVOS LOGS IMPORTANTES AQUÍ ***
    console.log(`DEBUG: (inside startLoading) allProcesses.value after push:`, allProcesses.value)
    console.log(`DEBUG: (inside startLoading) isLoading.value after push:`, isLoading.value)
    // **********************************

    startWebSocket(newProcess)

    // Aseguramos que la modal principal se muestre y la lista de procesos se oculte
    isMinimized.value = false
    // showProcessList.value = false

    console.log(`DEBUG: isMinimized after startLoading: ${isMinimized.value}`)
    console.log(`DEBUG: showProcessList after startLoading: ${showProcessList.value}`)

    console.log(`✅ [START] Proceso creado y WebSocket iniciado para ${batchId}`)
    return true
  }

  const stopLoading = () => {
    console.log(`🛑 [STOP] Deteniendo carga global`)
    allProcesses.value.forEach((p) => {
      if (p.status === "active" || p.status === "queued") {
        stopWebSocket(p)
        p.status = "error"
        p.completed_at = new Date().toISOString()
      }
    })
  }

  const minimize = () => {
    isMinimized.value = true
  }

  const restore = () => {
    isMinimized.value = false
  }

  const hasActiveProcess = computed(() => {
    return activeProcesses.value.length > 0
  })

  const showProcessListModal = () => {
    console.log(`📋 [LIST] Mostrando lista de procesos`)
    showProcessList.value = true
  }

  const hideProcessListModal = () => {
    console.log(`📋 [LIST] Ocultando lista de procesos`)
    showProcessList.value = false
  }

  const removeProcess = (batchId: string) => {
    console.log(`🗑️ [REMOVE] Eliminando proceso: ${batchId}`)
    const index = allProcesses.value.findIndex((p) => p.batch_id === batchId)
    if (index !== -1) {
      const processToRemove = allProcesses.value[index]
      stopWebSocket(processToRemove)
      allProcesses.value.splice(index, 1)
    }
  }

  const showDataProcess = async (batchId: string) => {
    console.log(`[SHOW] Ver data del proceso: ${batchId}`)
    const axiosInstance = (window as any).useAxios
    const { response, data } = await axiosInstance(`/process/${batchId}/errors`).get()
    if (response.status == 200 && data) {
      console.log("Errores del proceso:", data)
    }
  }

  const clearCompletedProcesses = () => {
    console.log(`🧹 [CLEAR] Limpiando procesos completados`)
    const beforeCount = allProcesses.value.length
    allProcesses.value = allProcesses.value.filter((p) => p.status !== "completed" && p.status !== "error")
    const afterCount = allProcesses.value.length
    console.log(`✅ [CLEAR] Eliminados ${beforeCount - afterCount} procesos completados`)
  }

  const cleanup = () => {
    console.log(`🧹 [CLEANUP] Limpiando`)
    allProcesses.value.forEach((p) => stopWebSocket(p))
    allProcesses.value = []
    isMinimized.value = false
    showProcessList.value = false
  }

  const onCompleted = (callback: (batchId: string) => void) => {
    callbacks.completed.push(callback)
  }

  const onError = (callback: (batchId: string, error: any) => void) => {
    callbacks.error.push(callback)
  }

  const onProgressUpdated = (callback: (batchId: string, progress: number) => void) => {
    callbacks.progressUpdated.push(callback)
  }

  const getUserProcesses = async (user_id: string) => {
    console.log("📡 [LOAD] Cargando procesos del usuario")
    try {
      const { response, data } = await useAxios(`/processBatch/getUserProcesses/${user_id}`).get();

      if (response.status === 200 && data.processes) {
        console.log(`✅ [LOAD] ${data.processes.length} procesos cargados`)

        const loadedProcessesMap = new Map(data.processes.map((p) => [p.batch_id, p]))
        const updatedAllProcesses: ImportProcess[] = []
        const activeOrQueuedProcessesToStartWs: ImportProcess[] = []

        allProcesses.value.forEach((existingProcess) => {
          if (loadedProcessesMap.has(existingProcess.batch_id)) {
            const backendProcess = loadedProcessesMap.get(existingProcess.batch_id)
            console.log("backendProcess",backendProcess);
            
            Object.assign(existingProcess, {
              batch_id: backendProcess.batch_id,
              file_name: backendProcess.metadata.file_name,
              progress: backendProcess.progress,
              current_element: backendProcess.current_element,
              current_action: backendProcess.current_action,
              status: backendProcess.status,
              started_at: backendProcess.started_at,
              completed_at: backendProcess.completed_at,
              metadata: backendProcess.metadata || {},
            })

            if (existingProcess.status === "active" || existingProcess.status === "queued") {
              activeOrQueuedProcessesToStartWs.push(existingProcess)
            }
            updatedAllProcesses.push(existingProcess)
            loadedProcessesMap.delete(existingProcess.batch_id)
          } else {
            if (existingProcess.status !== "completed" && existingProcess.status !== "error") {
              updatedAllProcesses.push(existingProcess)
            }
          }
        })

        loadedProcessesMap.forEach((newBackendProcess) => {
            console.log("newBackendProcess",newBackendProcess);

          const newProcess: ImportProcess = {
            batch_id: newBackendProcess.batch_id,
            file_name: newBackendProcess.metadata.file_name,
            progress: newBackendProcess.progress,
            current_element: newBackendProcess.current_element,
            current_action: newBackendProcess.current_action,
            status: newBackendProcess.status,
            started_at: newBackendProcess.started_at,
            completed_at: newBackendProcess.completed_at,
            metadata: newBackendProcess.metadata || {},
          }
          updatedAllProcesses.push(newProcess)
          if (newProcess.status === "active" || newProcess.status === "queued") {
            activeOrQueuedProcessesToStartWs.push(newProcess)
          }
        })

        allProcesses.value = updatedAllProcesses

        activeOrQueuedProcessesToStartWs.forEach((p) => {
          if (!p.websocket_channel) {
            startWebSocket(p)
          }
        })


        console.log(`DEBUG: allProcesses after getUserProcesses merge:`, allProcesses.value)
        console.log(`DEBUG: isLoading after getUserProcesses merge:`, isLoading.value)
      }
    } catch (error) {
      console.error("❌ [LOAD] Error cargando procesos:", error)
    }
  }

  return {
    isLoading,
    currentProcess,
    currentProgress,
    debugInfo,
    isMinimized,
    showProcessList,
    allProcesses,
    activeProcesses,
    queuedProcesses,
    completedProcesses,
    sortedProcesses,
    hasMultipleProcesses,
    startLoading,
    stopLoading,
    minimize,
    restore,
    hasActiveProcess,
    cleanup,
    showProcessListModal,
    hideProcessListModal,
    removeProcess,
    getUserProcesses,
    showDataProcess,
    clearCompletedProcesses,
    onCompleted,
    onError,
    onProgressUpdated,
  }
}
