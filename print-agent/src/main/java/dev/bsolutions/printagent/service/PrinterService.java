package dev.bsolutions.printagent.service;

import dev.bsolutions.printagent.config.AgentProperties;
import dev.bsolutions.printagent.dto.PrinterInfo;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

import javax.print.*;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import java.io.OutputStream;
import java.net.Socket;
import java.util.Arrays;
import java.util.List;

@Service
public class PrinterService {

    private static final Logger log = LoggerFactory.getLogger(PrinterService.class);

    private final AgentProperties props;
    private final EscPosBuilder escPosBuilder;

    public PrinterService(AgentProperties props, EscPosBuilder escPosBuilder) {
        this.props = props;
        this.escPosBuilder = escPosBuilder;
    }

    /** Returns all system printers visible via javax.print. */
    public List<PrinterInfo> listPrinters() {
        PrintService defaultService = PrintServiceLookup.lookupDefaultPrintService();
        String defaultName = defaultService != null ? defaultService.getName() : "";

        return Arrays.stream(PrintServiceLookup.lookupPrintServices(null, null))
                .map(s -> new PrinterInfo(s.getName(), s.getName().equals(defaultName)))
                .toList();
    }

    /**
     * Sends a print job to the specified printer.
     *
     * @param printerName     System printer name (for USB/WINDOWS_SHARED) or host:port (for NETWORK)
     * @param connectionType  USB | WINDOWS_SHARED | NETWORK
     * @param content         Pre-formatted text content from TicketPrintFormatterService
     */
    public void print(String printerName, String connectionType, String content) throws Exception {
        byte[] data = escPosBuilder.reset()
                .text(content, props.charset())
                .feed(2)
                .cut()
                .build();

        if (props.logJobs()) {
            log.info("Print job: {} bytes → {} ({})", data.length, printerName, connectionType);
        }

        if ("NETWORK".equalsIgnoreCase(connectionType)) {
            printNetwork(printerName, data);
        } else {
            printLocal(printerName, data);
        }
    }

    private void printLocal(String printerName, byte[] data) throws Exception {
        PrintService target = findPrinter(printerName);

        if (target == null) {
            // Fallback: try the default printer
            target = PrintServiceLookup.lookupDefaultPrintService();
        }

        if (target == null) {
            throw new IllegalStateException("No se encontró impresora: " + printerName);
        }

        DocFlavor flavor = DocFlavor.BYTE_ARRAY.AUTOSENSE;
        PrintRequestAttributeSet attrs = new HashPrintRequestAttributeSet();
        Doc doc = new SimpleDoc(data, flavor, null);
        DocPrintJob job = target.createPrintJob();
        job.print(doc, attrs);

        log.info("Impresión completada en: {}", target.getName());
    }

    private void printNetwork(String hostPort, byte[] data) throws Exception {
        String host;
        int port = 9100;

        if (hostPort.contains(":")) {
            String[] parts = hostPort.split(":", 2);
            host = parts[0];
            port = Integer.parseInt(parts[1]);
        } else {
            host = hostPort;
        }

        try (Socket socket = new Socket(host, port);
             OutputStream out = socket.getOutputStream()) {
            out.write(data);
            out.flush();
        }

        log.info("Impresión completada en red: {}:{}", host, port);
    }

    private PrintService findPrinter(String name) {
        if (name == null || name.isBlank()) return null;
        for (PrintService service : PrintServiceLookup.lookupPrintServices(null, null)) {
            if (service.getName().equalsIgnoreCase(name)
                    || service.getName().toLowerCase().contains(name.toLowerCase())) {
                return service;
            }
        }
        return null;
    }
}
