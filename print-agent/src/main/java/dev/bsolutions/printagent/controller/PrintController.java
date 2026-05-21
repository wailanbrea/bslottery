package dev.bsolutions.printagent.controller;

import dev.bsolutions.printagent.dto.PrintRequest;
import dev.bsolutions.printagent.service.PrinterService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/api")
public class PrintController {

    private static final Logger log = LoggerFactory.getLogger(PrintController.class);

    private final PrinterService printerService;

    public PrintController(PrinterService printerService) {
        this.printerService = printerService;
    }

    /** Public — no token required. Used by the web frontend to check if agent is running. */
    @GetMapping("/status")
    public ResponseEntity<Map<String, Object>> status() {
        return ResponseEntity.ok(Map.of(
                "status", "running",
                "version", "1.0.0",
                "os", System.getProperty("os.name"),
                "java", System.getProperty("java.version")
        ));
    }

    /** Lists printers visible to the OS. Token required. */
    @GetMapping("/printers")
    public ResponseEntity<?> printers() {
        return ResponseEntity.ok(Map.of("printers", printerService.listPrinters()));
    }

    /** Sends a print job to the specified printer. Token required. */
    @PostMapping("/print")
    public ResponseEntity<?> print(@RequestBody PrintRequest req) {
        if (req.content() == null || req.content().isBlank()) {
            return ResponseEntity.badRequest().body(Map.of("success", false, "error", "El contenido está vacío"));
        }

        try {
            printerService.print(
                    req.printerName(),
                    req.connectionType() != null ? req.connectionType() : "USB",
                    req.content()
            );
            return ResponseEntity.ok(Map.of(
                    "success", true,
                    "job_uuid", req.jobUuid() != null ? req.jobUuid() : ""
            ));
        } catch (Exception e) {
            log.error("Error imprimiendo job {}: {}", req.jobUuid(), e.getMessage());
            return ResponseEntity.status(500).body(Map.of("success", false, "error", e.getMessage()));
        }
    }

    /** Sends a test page to verify the printer is working. Token required. */
    @PostMapping("/test")
    public ResponseEntity<?> testPrint(@RequestBody Map<String, String> req) {
        String printerName = req.getOrDefault("printer_name", "");
        String connType    = req.getOrDefault("connection_type", "USB");
        int    width       = "80MM".equals(req.getOrDefault("paper_width", "58MM")) ? 56 : 32;

        String content = String.join("\n",
                repeat('-', width),
                center("BSLotery Print Agent", width),
                center("Prueba de impresion", width),
                repeat('-', width),
                "Impresora : " + printerName,
                "Conexion  : " + connType,
                "Version   : 1.0.0",
                repeat('-', width),
                center("OK - Impresora lista", width),
                repeat('-', width),
                "", ""
        );

        try {
            printerService.print(printerName, connType, content);
            return ResponseEntity.ok(Map.of("success", true));
        } catch (Exception e) {
            log.error("Error en prueba de impresion: {}", e.getMessage());
            return ResponseEntity.status(500).body(Map.of("success", false, "error", e.getMessage()));
        }
    }

    private static String repeat(char c, int n) {
        return String.valueOf(c).repeat(n);
    }

    private static String center(String text, int width) {
        if (text.length() >= width) return text.substring(0, width);
        int pad = (width - text.length()) / 2;
        return " ".repeat(pad) + text;
    }
}
