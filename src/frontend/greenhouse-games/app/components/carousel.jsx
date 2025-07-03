import { Box, Button, Image, Text, Flex } from "@chakra-ui/react";

const cardData = [
  {
    title: "Reduce food waste",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/200/200",
  },
  {
    title: "Save the world!",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/201/200",
  },
  {
    title: "Help out the homeless",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/202/200",
  },
];

const Carousel = () => {
  return (
    <Box overflowX="auto" py={4}>
      <Flex gap={4} px={4} width="max-content">
        {cardData.map((item, index) => (
          <Box
            key={index}
            minW="180px"
            maxW="180px"
            bg="white"
            rounded="lg"
            shadow="md"
            overflow="hidden"
            flexShrink={0}
          >
            <Image
              src={item.imageUrl}
              alt={item.title}
              borderRadius="25"
              boxSize="140px"
              mx="auto"
              mt={4}
              objectFit="cover"
            />
            <Box p={4} textAlign="center">
              <Text fontWeight="bold" fontSize="lg" mb={1}>
                {item.title}
              </Text>
              <Text fontSize="sm" color="gray.600">
                {item.organiser}
              </Text>
            </Box>
            <Flex justify="center" gap={2} pb={4}>
              <Button size="sm" variant="outline">
                View
              </Button>
              <Button size="sm" colorScheme="blue">
                Join
              </Button>
            </Flex>
          </Box>
        ))}
      </Flex>
    </Box>
  );
};

export default Carousel;
